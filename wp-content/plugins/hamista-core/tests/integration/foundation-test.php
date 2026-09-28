<?php
/**
 * Integration tests for the core foundation (plan Tasks 1.1 + 1.3).
 *
 * Run: tools/tests/run-integration.sh wp-content/plugins/hamista-core/tests/integration/foundation-test.php
 *
 * Every fixture is uniquely named and removed again; the shared database is
 * never reset. Options changed for a test are restored in `finally` blocks.
 *
 * @package Hamista\Core\Tests
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Core\Installer;
use Hamista\Core\Modules\Abstract_Module;
use Hamista\Core\Modules\Module_Registry;
use Hamista\Core\Support\Html;
use Hamista\Core\Support\Mailer;
use Hamista\Core\Support\Private_Storage;

/**
 * Updates an option for the duration of a callback, then restores it.
 *
 * @param string   $option   Option name.
 * @param mixed    $value    Temporary value.
 * @param callable $callback Test code.
 */
function hm_itest_with_option( string $option, $value, callable $callback ): void {
	$missing  = new stdClass();
	$original = get_option( $option, $missing );
	update_option( $option, $value );
	try {
		$callback();
	} finally {
		if ( $original === $missing ) {
			delete_option( $option );
		} else {
			update_option( $option, $original );
		}
	}
}

/**
 * Recursively deletes a directory created by a test.
 *
 * @param string $dir Directory.
 */
function hm_itest_rmdir( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $items as $item ) {
		$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}
	rmdir( $dir );
}

/**
 * Early (pre-WordPress) code that registers four test modules.
 *
 * @return string
 */
function hm_itest_module_fixture(): string {
	return <<<'PHP'
WP_CLI::add_wp_hook( 'hamista_register_modules', static function ( $registry ) {
	$make = static function ( string $id, array $requires, bool $default ) {
		return new class( $id, $requires, $default ) extends \Hamista\Core\Modules\Abstract_Module {
			private $module_id; private $module_requires; private $module_default;
			public function __construct( $id, $requires, $default ) { $this->module_id = $id; $this->module_requires = $requires; $this->module_default = $default; }
			public function id(): string { return $this->module_id; }
			public function title(): string { return 'Test module'; }
			public function description(): string { return ''; }
			public function requires(): array { return $this->module_requires; }
			public function default_enabled(): bool { return $this->module_default; }
			public function boot(): void {
				$GLOBALS['hm_itest_booted'][ $this->module_id ] = current_action() . ':' . $GLOBALS['wp_filter'][ current_action() ]->current_priority();
			}
		};
	};
	$registry->add( $make( 'hm_itest_default_on', [], true ) );
	$registry->add( $make( 'hm_itest_default_off', [], false ) );
	$registry->add( $make( 'hm_itest_needs_wc', [ 'woocommerce' ], true ) );
	$registry->add( $make( 'hm_itest_needs_unknown', [ 'no-such-plugin' ], true ) );
} );
PHP;
}

/**
 * Boots WordPress in a fresh process with the test modules and reports what booted.
 *
 * @return array{booted:array,enabled:array}
 */
function hm_itest_boot_modules_in_new_process(): array {
	$report = 'echo wp_json_encode( [ "booted" => $GLOBALS["hm_itest_booted"] ?? [], "enabled" => array_map( "hamista_module_enabled", [ "on" => "hm_itest_default_on", "off" => "hm_itest_default_off", "wc" => "hm_itest_needs_wc", "unknown" => "hm_itest_needs_unknown", "missing" => "hm_itest_not_registered" ] ) ] );';
	$result = hm_wp_cli( 'eval ' . escapeshellarg( $report ), hm_itest_module_fixture() );
	assert_same( 0, $result['code'], 'child process failed: ' . $result['stderr'] );
	$data = json_decode( $result['stdout'], true );
	assert_true( is_array( $data ), 'child output: ' . $result['stdout'] . ' ' . $result['stderr'] );
	return $data;
}

function test_core_is_active_and_booted_on_plugins_loaded() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	assert_true( is_plugin_active( 'hamista-core/hamista-core.php' ) );
	assert_same( '1.0.0', HAMISTA_CORE_VERSION );
	assert_true( hamista_core() instanceof \Hamista\Core\Plugin );
	assert_true( hamista_core() === \Hamista\Core\Plugin::instance() );
	assert_same( 1, did_action( 'hamista_core_loaded' ) );
	assert_same( 1, did_action( 'hamista_register_modules' ), 'modules are collected on init' );
	assert_same( 1, has_action( 'init', [ hamista_core(), 'boot_modules' ] ), 'modules boot on init priority 1' );
	assert_same( 0, has_action( 'init', [ hamista_core(), 'load_textdomain' ] ), 'text domain loads on init priority 0' );
}

function test_activation_is_clean_and_installs_capabilities_storage_and_version() {
	$errors = [];
	set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		static function ( $severity, $message, $file, $line ) use ( &$errors ) {
			$errors[] = "{$message} ({$file}:{$line})";
			return true;
		}
	);
	try {
		Installer::activate( false );
	} finally {
		restore_error_handler();
	}
	assert_same( [], $errors, 'activation raised PHP errors' );

	assert_true( get_role( 'administrator' )->has_cap( 'hamista_view_reports' ) );
	assert_true( get_role( 'shop_manager' )->has_cap( 'hamista_view_reports' ), 'WooCommerce shop managers get it too' );
	assert_false( get_role( 'customer' )->has_cap( 'hamista_view_reports' ) );
	assert_same( '1.0.0', get_option( 'hamista_core_version' ) );
	assert_same( 1, (int) get_option( Installer::FLUSH_OPTION ), 'a rewrite flush is scheduled' );

	Installer::maybe_flush_rewrite_rules();
	assert_same( 0, (int) get_option( Installer::FLUSH_OPTION ), 'the flush runs once' );

	foreach ( [ '.htaccess', 'index.php', 'web.config' ] as $guard ) {
		assert_true( is_file( Private_Storage::base_path() . '/' . $guard ), "base guard {$guard}" );
	}

	$log = WP_CONTENT_DIR . '/debug.log';
	if ( is_file( $log ) ) {
		$ours = preg_grep( '#/plugins/hamista-core/#', (array) file( $log ) );
		assert_same( [], array_values( $ours ), 'debug.log has entries from hamista-core' );
	}
}

function test_test_module_boots_on_init_1_only_when_enabled_and_requirements_met() {
	$data = hm_itest_boot_modules_in_new_process();
	assert_same(
		[
			'hm_itest_default_on' => 'init:1',
			'hm_itest_needs_wc'   => 'init:1',
		],
		$data['booted'],
		'enabled modules with met requirements boot on init:1; others do not'
	);
	assert_same(
		[
			'on'      => true,
			'off'     => false,
			'wc'      => true,
			'unknown' => false,
			'missing' => false,
		],
		$data['enabled']
	);

	// Switch the toggles: the default-on module off, the default-off module on.
	hm_itest_with_option(
		'hamista_modules',
		array_merge(
			(array) get_option( 'hamista_modules', [] ),
			[
				'hm_itest_default_on'    => false,
				'hm_itest_default_off'   => '1',
				'hm_itest_needs_unknown' => true,
			]
		),
		static function () {
			$data = hm_itest_boot_modules_in_new_process();
			assert_same(
				[
					'hm_itest_default_off' => 'init:1',
					'hm_itest_needs_wc'    => 'init:1',
				],
				$data['booted'],
				'toggles win over defaults; unknown requirements fail closed even when enabled'
			);
		}
	);
}

function test_module_registry_rules() {
	$registry = new Module_Registry();
	$module   = new class() extends Abstract_Module {
		public int $boots = 0;
		public function id(): string {
			return 'hm_itest_registry';
		}
		public function title(): string {
			return 'Registry test';
		}
		public function description(): string {
			return '';
		}
		public function requires(): array {
			return [ 'woocommerce', 'rank-math', 'no-such-plugin' ];
		}
		public function boot(): void {
			++$this->boots;
		}
	};
	$simple   = new class() extends Abstract_Module {
		public int $boots = 0;
		public function id(): string {
			return 'hm_itest_simple';
		}
		public function title(): string {
			return 'Simple';
		}
		public function description(): string {
			return '';
		}
		public function boot(): void {
			++$this->boots;
		}
	};

	$registry->add( $module );
	$registry->add( $simple );
	assert_same( 'content', $simple->group() );
	assert_same( [ 'hm_itest_registry', 'hm_itest_simple' ], array_keys( $registry->all() ) );
	assert_true( $registry->get( 'hm_itest_simple' ) === $simple );
	assert_same( null, $registry->get( 'nope' ) );
	assert_false( $registry->requirements_met( $module ) );
	assert_same( 'Requires Rank Math and an unknown component (no-such-plugin).', $registry->unavailable_reason( $module ) );
	assert_same( '', $registry->unavailable_reason( $simple ) );

	$registry->boot_enabled();
	$registry->boot_enabled();
	assert_same( 1, $simple->boots, 'boot_enabled() is idempotent' );
	assert_same( 0, $module->boots, 'unmet requirements prevent booting' );
	assert_true( $registry->is_booted( 'hm_itest_simple' ) );
}

function test_option_defaults_resolve_and_the_cache_follows_changes() {
	$option = 'hamista_itest_' . wp_generate_password( 6, false, false );
	hamista_register_option_defaults(
		$option,
		[
			'color' => 'orange',
			'size'  => 3,
		]
	);
	try {
		assert_same( 'orange', hamista_get_option( $option, 'color' ) );
		assert_same( 'fallback', hamista_get_option( $option, 'missing', 'fallback' ) );

		update_option( $option, [ 'color' => 'purple' ] );
		assert_same( 'purple', hamista_get_option( $option, 'color' ), 'cache dropped on add_option' );
		assert_same( 3, hamista_get_option( $option, 'size' ), 'unsaved keys keep their default' );

		update_option( $option, [ 'color' => 'red' ] );
		assert_same( 'red', hamista_get_option( $option, 'color' ), 'cache dropped on update_option' );

		delete_option( $option );
		assert_same( 'orange', hamista_get_option( $option, 'color' ), 'cache dropped on delete_option' );
	} finally {
		delete_option( $option );
	}

	assert_same( 'REMOTE_ADDR', hamista_get_option( 'hamista_core', 'ip_header' ) );
	assert_same( 'jalali', hamista_get_option( 'hamista_core', 'calendar' ) );
}

function test_account_endpoint_is_merged_into_the_menu_by_position() {
	$id = 'hm-itest-' . strtolower( wp_generate_password( 6, false, false ) );
	hamista_register_account_endpoint(
		$id,
		[
			'title'    => 'Itest Endpoint',
			'icon'     => 'key',
			'group'    => 'shop',
			'position' => 30,
			'callback' => static function ( $value ) {
				echo 'value:' . esc_html( $value );
			},
		]
	);

	$keys = array_keys( wc_get_account_menu_items() );
	assert_contains( $id, $keys );
	assert_same( array_search( 'downloads', $keys, true ) + 1, array_search( $id, $keys, true ), 'position 30 sits after downloads (20)' );
	assert_same( 'Itest Endpoint', wc_get_account_menu_items()[ $id ] );
	assert_same( 'customer-logout', end( $keys ), 'logout (100) stays last' );

	$registered = hamista_account_endpoints()[ $id ];
	assert_same( [ 'shop', 'key', 30 ], [ $registered['group'], $registered['icon'], $registered['position'] ] );
	assert_true( str_ends_with( hamista_core()->account_endpoints()->url( $id ), "/my-account/{$id}/" ) );
	assert_contains( $id, WC()->query->get_query_vars() );

	ob_start();
	do_action( "woocommerce_account_{$id}_endpoint", 'abc' );
	assert_same( 'value:abc', ob_get_clean() );
	assert_same( 'Itest Endpoint', WC()->query->get_endpoint_title( $id ) );
}

function test_account_endpoint_renders_over_http_for_a_logged_in_customer() {
	$id     = 'hm-itest-' . strtolower( wp_generate_password( 6, false, false ) );
	$marker = 'hm-itest-marker-' . wp_generate_password( 8, false, false );
	$plugin = hm_mu_plugin(
		sprintf(
			'add_action( "init", static function () { if ( function_exists( "hamista_register_account_endpoint" ) ) { hamista_register_account_endpoint( %s, [ "title" => "Itest Panel", "position" => 30, "callback" => static function ( $value ) { echo "<p>" . %s . ":" . esc_html( $value ) . "</p>"; } ] ); } }, 5 );',
			var_export( $id, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			var_export( $marker, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		)
	);
	try {
		$cookies = hm_login( 'customer', 'customer' );

		$page = hm_http( 'GET', "/my-account/{$id}/", [ 'cookies' => $cookies ] );
		assert_same( 200, $page['status'] );
		assert_contains( "{$marker}:", $page['body'], 'endpoint content' );
		assert_contains( "woocommerce-MyAccount-navigation-link--{$id}", $page['body'], 'menu item' );
		assert_contains( 'Itest Panel', $page['body'], 'title' );

		$value = hm_http( 'GET', "/my-account/{$id}/42/", [ 'cookies' => $cookies ] );
		assert_contains( "{$marker}:42", $value['body'], 'the callback receives the endpoint value' );
	} finally {
		unlink( $plugin );
		// Next request sees a different endpoint set and flushes the rules back.
		hm_http( 'GET', '/' );
	}
}

function test_mailer_renders_rtl_for_fa_ir_and_ltr_for_en_us() {
	$args = [
		'heading' => 'سفارش شما ثبت شد',
		'body'    => '<p>متن ایمیل <script>alert(1)</script><strong>مهم</strong></p>',
		'button'  => [
			'text' => 'مشاهده سفارش',
			'url'  => 'https://example.com/order/1',
		],
	];

	assert_true( switch_to_locale( 'fa_IR' ), 'fa_IR must be installed in the test environment' );
	try {
		$html = ( new Mailer() )->render( 'Subject', $args );
	} finally {
		restore_previous_locale();
	}
	assert_contains( '<html lang="fa-IR" dir="rtl">', $html );
	assert_contains( 'text-align:right', $html );
	assert_contains( 'سفارش شما ثبت شد', $html );
	assert_contains( '<strong>مهم</strong>', $html );
	assert_false( str_contains( $html, '<script>' ), 'body goes through wp_kses_post' );
	assert_contains( 'href="https://example.com/order/1"', $html );
	assert_contains( 'مشاهده سفارش</a>', $html, 'call-to-action button' );
	assert_contains( 'linear-gradient(270deg,#FF7A1A,#FF3B5C,#8B5CF6)', $html, 'brand bar starts at the inline start' );

	switch_to_locale( 'en_US' );
	try {
		$english = ( new Mailer() )->render(
			'Subject',
			[
				'heading' => 'Hello',
				'button'  => [ 'text' => 'Open' ],
			]
		);
	} finally {
		restore_previous_locale();
	}
	assert_contains( '<html lang="en-US" dir="ltr">', $english );
	assert_contains( 'text-align:left', $english );
	assert_false( str_contains( $english, 'Open</a>' ), 'no button without both text and url' );
}

function test_mailer_uses_the_recipients_locale_and_the_configured_sender() {
	$customer = get_user_by( 'login', 'customer' );
	assert_true( $customer instanceof WP_User );
	$had_locale = metadata_exists( 'user', $customer->ID, 'locale' );
	$old_locale = get_user_meta( $customer->ID, 'locale', true );
	update_user_meta( $customer->ID, 'locale', 'fa_IR' );

	$sent    = [];
	$capture = static function ( $short_circuit, $atts ) use ( &$sent ) {
		$sent[] = $atts;
		return true;
	};
	add_filter( 'pre_wp_mail', $capture, 1, 2 );
	$locale_before = determine_locale();

	try {
		hm_itest_with_option(
			'hamista_core',
			array_merge(
				(array) get_option( 'hamista_core', [] ),
				[
					'mail_from_email' => 'shop@example.com',
					'mail_from_name'  => "Hamista\r\nBcc: evil@example.com",
				]
			),
			static function () use ( $customer ) {
				assert_true(
					hamista_mail(
						$customer->user_email,
						'Hi',
						[
							'heading' => 'Hi',
							'user_id' => $customer->ID,
						]
					)
				);
			}
		);
		assert_same( $locale_before, determine_locale(), 'the locale is restored after rendering' );
	} finally {
		remove_filter( 'pre_wp_mail', $capture, 1 );
		if ( $had_locale ) {
			update_user_meta( $customer->ID, 'locale', $old_locale );
		} else {
			delete_user_meta( $customer->ID, 'locale' );
		}
	}

	assert_same( 1, count( $sent ) );
	assert_contains( 'dir="rtl"', $sent[0]['message'], "rendered in the customer's locale" );
	assert_contains( 'Content-Type: text/html; charset=UTF-8', $sent[0]['headers'] );
	assert_contains( 'From: "HamistaBcc: evil@example.com" <shop@example.com>', $sent[0]['headers'], 'line breaks are stripped from the name' );
}

function test_private_storage_guards_validation_and_records() {
	$bucket  = 'hm-itest-' . strtolower( wp_generate_password( 6, false, false ) );
	$storage = hamista_private_storage( $bucket );
	$tmp     = get_temp_dir() . 'hm-itest-' . wp_generate_password( 6, false, false );
	wp_mkdir_p( $tmp );

	try {
		assert_true( $storage->ensure_protected() );
		foreach ( [ Private_Storage::base_path(), $storage->base_dir() ] as $dir ) {
			foreach ( [ '.htaccess', 'index.php', 'web.config' ] as $guard ) {
				assert_true( is_file( "{$dir}/{$guard}" ), "{$dir}/{$guard}" );
			}
		}
		$htaccess = (string) file_get_contents( $storage->base_dir() . '/.htaccess' );
		assert_contains( 'Require all denied', $htaccess );
		assert_contains( 'Deny from all', $htaccess );

		// A real PNG is stored under a random name.
		$image = imagecreatetruecolor( 4, 4 );
		imagepng( $image, "{$tmp}/source.png" );
		$record = $storage->store_file( "{$tmp}/source.png", 'Photo 1.png', [ 'subdir' => 'Tickets/42/../x' ] );
		assert_true( is_array( $record ), is_wp_error( $record ) ? $record->get_error_message() : '' );
		$keys = array_keys( $record );
		sort( $keys );
		assert_same( [ 'mime', 'name', 'path', 'size', 'uploaded_at' ], $keys, 'the spec §14 record shape' );
		assert_same( filesize( "{$tmp}/source.png" ), $record['size'] );
		assert_same( 'image/png', $record['mime'] );
		assert_same( 'Photo-1.png', $record['name'] );
		assert_same( 1, preg_match( '#^tickets/42/x/[a-f0-9]{32}\.png$#', $record['path'] ), 'path: ' . $record['path'] );
		assert_same( 1, preg_match( '/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', $record['uploaded_at'] ) );
		$stored = $storage->path( $record );
		assert_true( is_string( $stored ) && is_file( $stored ) );
		assert_same( '0640', substr( sprintf( '%o', fileperms( $stored ) ), -4 ) );
		assert_true( is_file( $storage->base_dir() . '/tickets/42/x/index.php' ), 'subdirectories get an index.php' );

		// A PNG named .jpg is stored as what it really is.
		$renamed = $storage->store_file( "{$tmp}/source.png", 'camera.jpg' );
		assert_same( [ 'image/png', 'camera.png' ], [ $renamed['mime'], $renamed['name'] ] );

		// Other allowed types, validated by content.
		file_put_contents( "{$tmp}/notes.txt", "Plain notes\nline two\n" );
		assert_same( 'text/plain', $storage->store_file( "{$tmp}/notes.txt", 'notes.txt' )['mime'] );
		file_put_contents( "{$tmp}/doc.pdf", "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n" );
		assert_same( 'application/pdf', $storage->store_file( "{$tmp}/doc.pdf", 'doc.pdf' )['mime'] );

		// Rejections.
		file_put_contents( "{$tmp}/shell.txt", '<?php echo "owned"; ?>' );
		$errors = [
			'hamista_file_name_blocked'     => $storage->store_file( "{$tmp}/shell.txt", 'shell.php.jpg' ),
			'hamista_file_type_mismatch'    => $storage->store_file( "{$tmp}/shell.txt", 'image.jpg' ),
			'hamista_file_type_not_allowed' => $storage->store_file( "{$tmp}/notes.txt", 'notes.svg' ),
			'hamista_file_too_large'        => $storage->store_file( "{$tmp}/notes.txt", 'notes.txt', [ 'max_size' => 5 ] ),
			'hamista_file_not_uploaded'     => $storage->store_upload(
				[
					'name'     => 'notes.txt',
					'tmp_name' => "{$tmp}/notes.txt",
					'error'    => UPLOAD_ERR_OK,
				]
			),
			'hamista_upload_error'          => $storage->validate(
				[
					'name'  => 'a.txt',
					'error' => UPLOAD_ERR_INI_SIZE,
				]
			),
		];
		foreach ( $errors as $code => $result ) {
			assert_true( is_wp_error( $result ), "{$code} expected" );
			assert_same( $code, $result->get_error_code() );
		}
		assert_true(
			$storage->validate(
				[
					'name'     => 'notes.txt',
					'tmp_name' => "{$tmp}/notes.txt",
				]
			)
		);

		// Paths never leave the bucket, and only stored files resolve.
		assert_false( $storage->path( [ 'path' => '../../../../wp-config.php' ] ) );
		assert_false( $storage->path( [ 'path' => '../index.php' ] ) );
		assert_false( $storage->path( [ 'path' => '' ] ) );
		assert_false( $storage->path( [ 'path' => '.htaccess' ] ), 'guard files are not records' );
		assert_false( $storage->path( [ 'path' => "tick\0ets/" . basename( $record['path'] ) ] ), 'NUL bytes are rejected, not fatal' );
		assert_false( $storage->path( [ 'path' => 'tickets/42/x/index.php' ] ) );
		assert_false( $storage->delete( [ 'path' => 'index.php' ] ) );
		assert_true( is_file( $storage->base_dir() . '/index.php' ) );

		assert_true( $storage->delete( $record ) );
		assert_false( file_exists( $stored ) );
		assert_false( $storage->delete( $record ), 'already deleted' );

		assert_throws( InvalidArgumentException::class, static fn() => hamista_private_storage( '../etc' ) );
	} finally {
		hm_itest_rmdir( $storage->base_dir() );
		hm_itest_rmdir( $tmp );
	}
}

function test_private_storage_send_streams_with_safe_headers() {
	$bucket  = 'hm-itest-' . strtolower( wp_generate_password( 6, false, false ) );
	$storage = hamista_private_storage( $bucket );
	$source  = get_temp_dir() . 'hm-itest-send-' . wp_generate_password( 6, false, false ) . '.txt';
	file_put_contents( $source, str_repeat( "Hamista private file\n", 100 ) );
	$record = $storage->store_file( $source, 'گزارش مالی.txt' );
	unlink( $source );
	assert_true( is_array( $record ) );

	$token  = wp_generate_password( 20, false, false );
	$plugin = hm_mu_plugin(
		sprintf(
			'add_action( "init", static function () { if ( isset( $_GET["hm_itest_send"] ) && %1$s === $_GET["hm_itest_send"] ) { hamista_private_storage( %2$s )->send( %3$s, isset( $_GET["inline"] ) ? "inline" : "attachment" ); } } );',
			var_export( $token, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			var_export( $bucket, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			var_export( $record, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		)
	);
	try {
		$download = hm_http( 'GET', '/?hm_itest_send=' . $token );
		assert_same( 200, $download['status'] );
		assert_same( str_repeat( "Hamista private file\n", 100 ), $download['body'] );
		assert_true( str_starts_with( $download['headers']['content-type'], 'text/plain' ), 'PHP may append ;charset=UTF-8 to text/*' );
		assert_same( 'nosniff', $download['headers']['x-content-type-options'] );
		assert_same( 'private, no-store', $download['headers']['cache-control'] );
		assert_same( "attachment; filename=\"download.txt\"; filename*=UTF-8''" . rawurlencode( 'گزارش-مالی.txt' ), $download['headers']['content-disposition'] );
		assert_false( isset( $download['headers']['content-security-policy'] ) );

		$inline = hm_http( 'GET', '/?inline=1&hm_itest_send=' . $token );
		assert_same( "default-src 'none'; sandbox", $inline['headers']['content-security-policy'] );
		assert_true( str_starts_with( $inline['headers']['content-disposition'], 'inline;' ) );
	} finally {
		unlink( $plugin );
		hm_itest_rmdir( $storage->base_dir() );
	}
}

function test_dates_and_digits_follow_calendar_locale_and_settings() {
	switch_to_locale( 'en_US' );
	try {
		assert_same( '2026-09-28', hamista_date( 'Y-m-d', '2026-09-28 12:00:00' ), 'en_US stays Gregorian' );
		assert_same( '', hamista_date( 'Y', 'not a date' ) );
		hm_itest_with_option(
			'hamista_core',
			array_merge( (array) get_option( 'hamista_core', [] ), [ 'persian_digits' => true ] ),
			static function () {
				assert_same( '1,500', hamista_price_digits( '1,500' ), 'no Persian digits outside fa_*' );
			}
		);
	} finally {
		restore_previous_locale();
	}

	switch_to_locale( 'fa_IR' );
	try {
		assert_same( '1405/07/06', hamista_date( 'Y/m/d', '2026-09-28 12:00:00' ) );
		assert_same( '1405/07/06', hamista_date( 'Y/m/d', new DateTimeImmutable( '2026-09-28 12:00:00', new DateTimeZone( 'UTC' ) ) ) );
		assert_same( '6 مهر 1405', hamista_date( 'j F Y', 1790596800 ) );

		hm_itest_with_option(
			'hamista_core',
			array_merge( (array) get_option( 'hamista_core', [] ), [ 'persian_digits' => true ] ),
			static function () {
				assert_same( '۱۴۰۵/۰۷/۰۶', hamista_date( 'Y/m/d', '2026-09-28 12:00:00' ) );
				assert_same(
					'<span class="amount-2" data-id="12">۱,۵۰۰&nbsp;&#1578;</span>',
					hamista_price_digits( '<span class="amount-2" data-id="12">1,500&nbsp;&#1578;</span>' ),
					'markup and character references keep their digits'
				);
			}
		);

		hm_itest_with_option(
			'hamista_core',
			array_merge( (array) get_option( 'hamista_core', [] ), [ 'calendar' => 'gregorian' ] ),
			static function () {
				assert_same( '2026/09/28', hamista_date( 'Y/m/d', '2026-09-28 12:00:00' ) );
			}
		);
	} finally {
		restore_previous_locale();
	}
}

function test_client_ip_honours_the_configured_header() {
	$server = $_SERVER;
	try {
		$_SERVER['REMOTE_ADDR']          = '203.0.113.9';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = 'unknown, 198.51.100.1:4433, 10.0.0.1';
		assert_same( '203.0.113.9', hamista_client_ip(), 'REMOTE_ADDR by default' );

		$core = (array) get_option( 'hamista_core', [] );
		hm_itest_with_option(
			'hamista_core',
			array_merge( $core, [ 'ip_header' => 'HTTP_X_FORWARDED_FOR' ] ),
			static function () {
				assert_same( '198.51.100.1', hamista_client_ip(), 'first valid IP, port stripped' );
				$_SERVER['HTTP_X_FORWARDED_FOR'] = 'garbage';
				assert_same( '203.0.113.9', hamista_client_ip(), 'falls back to REMOTE_ADDR' );
				$_SERVER['REMOTE_ADDR'] = 'nonsense';
				assert_same( '0.0.0.0', hamista_client_ip() );
			}
		);
		hm_itest_with_option(
			'hamista_core',
			array_merge( $core, [ 'ip_header' => 'HTTP_HOST' ] ),
			static function () {
				$_SERVER['REMOTE_ADDR'] = '2001:db8::1';
				assert_same( '2001:db8::1', hamista_client_ip(), 'headers outside the allow-list are ignored' );
			}
		);
	} finally {
		$_SERVER = $server;
	}
}

function test_rate_limit_counts_hits_in_transients() {
	$bucket = 'itest:' . wp_generate_password( 8, false, false );
	assert_true( hamista_rate_limit( $bucket, 2, 60 ) );
	assert_true( hamista_rate_limit( $bucket, 2, 60 ) );
	assert_false( hamista_rate_limit( $bucket, 2, 60 ) );
	( new \Hamista\Core\Support\Rate_Limiter( new \Hamista\Core\Support\Transient_Store() ) )->reset( $bucket );
	assert_true( hamista_rate_limit( $bucket, 2, 60 ), 'reset clears the transient' );
	( new \Hamista\Core\Support\Rate_Limiter( new \Hamista\Core\Support\Transient_Store() ) )->reset( $bucket );
}

function test_icons_render_accessible_inline_svg() {
	$check = hamista_icon( 'check' );
	assert_same( 1, preg_match( '#^<svg class="hm-icon hm-icon--check" [^>]*aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>$#', $check ), $check );
	assert_contains( 'stroke="currentColor"', $check );
	assert_contains( 'viewBox="0 0 24 24"', $check );

	$titled = hamista_icon(
		'bell',
		[
			'title' => 'Alerts <3',
			'class' => 'x-extra',
			'size'  => 20,
		]
	);
	assert_contains( 'role="img" aria-label="Alerts &lt;3"', $titled );
	assert_contains( '<title>Alerts &lt;3</title>', $titled );
	assert_contains( 'class="hm-icon hm-icon--bell x-extra"', $titled );
	assert_contains( 'width="20" height="20"', $titled );
	assert_false( str_contains( $titled, 'aria-hidden' ) );

	assert_same( '', hamista_icon( 'no-such-icon' ) );
	assert_contains( 'stroke="currentColor"', hamista_icon( 'x' ), 'x is the Lucide close icon' );
	assert_contains( 'hm-icon--brand', hamista_icon( 'brand-x' ) );
	assert_contains( 'fill="currentColor"', hamista_icon( 'instagram' ), 'unambiguous brand slugs resolve' );
	assert_contains( 'hm-icon--dir', hamista_icon( 'chevron-right' ) );
	assert_false( str_contains( hamista_icon( 'chevron-right', [ 'flip' => false ] ), 'hm-icon--dir' ) );

	// kses re-serialises `/>` as ` />` and compares names case-insensitively; nothing else may change.
	$normalize = static fn( string $html ): string => strtolower( str_replace( ' />', '/>', $html ) );
	foreach ( [ 'check', 'palette', 'brand-github', 'loader' ] as $name ) {
		$svg = hamista_icon( $name );
		assert_same( $normalize( $svg ), $normalize( wp_kses( $svg, Html::svg_kses() ) ), "{$name} survives svg_kses()" );
	}
}

function test_html_attrs_escapes_and_handles_special_values() {
	assert_same(
		' class="a b" disabled data-config="{&quot;k&quot;:1}" href="" title="&quot;&lt;&gt;" hidden',
		Html::attrs(
			[
				'class'       => [ 'a', '', 'b', 'a' ],
				'disabled'    => true,
				'aria-hidden' => false,
				'data-empty'  => null,
				'data-config' => [ 'k' => 1 ],
				'href'        => 'javascript:alert(1)',
				'title'       => '"<>',
				'hidden',
			]
		)
	);
}

function test_renderer_returns_empty_for_missing_components_and_renders_overrides() {
	assert_same( '', hamista_render( 'no-such-component' ) );
	assert_same( '', hamista_render( '../../hamista-core' ) );

	$template = get_temp_dir() . 'hm-itest-component-' . wp_generate_password( 6, false, false ) . '.php';
	file_put_contents( $template, '<?php defined( "ABSPATH" ) || exit; ?><div class="hm-itest"><?php echo esc_html( $args["name"] ); ?></div>' );
	$locate      = static function ( $found, $slug, $relative ) use ( $template ) {
		return 'components/hm-itest.php' === $relative ? $template : $found;
	};
	$args_filter = static function ( $args, $component ) {
		return 'hm-itest' === $component ? array_merge( $args, [ 'name' => $args['name'] . '!' ] ) : $args;
	};
	add_filter( 'hamista_locate_template', $locate, 10, 3 );
	add_filter( 'hamista_component_args', $args_filter, 10, 2 );
	wp_register_style( 'hamista-c-hm-itest', false, [], '1' );
	try {
		assert_same( '<div class="hm-itest">&lt;b&gt;!</div>', hamista_render( 'hm-itest', [ 'name' => '<b>' ] ) );
		assert_true( wp_style_is( 'hamista-c-hm-itest', 'enqueued' ), 'the component style is enqueued' );
	} finally {
		remove_filter( 'hamista_locate_template', $locate, 10 );
		remove_filter( 'hamista_component_args', $args_filter, 10 );
		wp_dequeue_style( 'hamista-c-hm-itest' );
		wp_deregister_style( 'hamista-c-hm-itest' );
		unlink( $template );
	}
}

function test_template_loader_rejects_unsafe_names() {
	$dir = HAMISTA_CORE_PATH . 'templates/';
	assert_same( $dir . 'emails/base.php', hamista_locate_template( 'hamista-core', 'emails/base.php', $dir ) );
	assert_same( '', hamista_locate_template( 'hamista-core', '../hamista-core.php', $dir ) );
	assert_same( '', hamista_locate_template( 'hamista-core', 'emails/../../hamista-core.php', $dir ) );
	assert_same( '', hamista_locate_template( 'hamista-core', 'php://filter/emails/base.php', $dir ) );
	assert_same( '', hamista_locate_template( 'hamista-core', 'emails/base.txt', $dir ) );
}

function test_shared_assets_are_registered_with_defer_and_config() {
	wp_deregister_script( 'hamista-ui' );
	wp_deregister_style( 'hamista-ui' );
	hamista_core()->register_assets();

	$script = wp_scripts()->registered['hamista-ui'];
	assert_true( str_ends_with( $script->src, 'hamista-core/assets/js/ui.js' ) || str_ends_with( $script->src, 'hamista-core/assets/js/ui.min.js' ) );
	assert_same( 'defer', wp_scripts()->get_data( 'hamista-ui', 'strategy' ) );
	assert_same( 1, wp_scripts()->get_data( 'hamista-ui', 'group' ), 'in the footer' );
	assert_contains( 'window.hamistaUI = {"i18n":{"copied":', implode( '', (array) wp_scripts()->get_data( 'hamista-ui', 'before' ) ) );
	assert_same( HAMISTA_CORE_VERSION, $script->ver );
	assert_true( str_contains( wp_styles()->registered['hamista-ui']->src, 'hamista-core/assets/css/ui' ) );

	// The Hamista theme registers an empty handle first; core must keep it.
	wp_deregister_style( 'hamista-ui' );
	wp_register_style( 'hamista-ui', false, [], '1' );
	hamista_core()->register_assets();
	assert_false( wp_styles()->registered['hamista-ui']->src );
}

function test_flash_messages_are_per_user_and_read_once() {
	$previous = get_current_user_id();
	$customer = get_user_by( 'login', 'customer' );
	wp_set_current_user( $customer->ID );
	try {
		hamista_flash( 'Saved', 'success' );
		hamista_flash( 'Odd type', 'bogus' );
		hamista_flash( '   ' );
		assert_same(
			[
				[
					'message' => 'Saved',
					'type'    => 'success',
				],
				[
					'message' => 'Odd type',
					'type'    => 'info',
				],
			],
			hamista_flash_messages()
		);
		assert_same( [], hamista_flash_messages(), 'messages are read once' );
	} finally {
		delete_transient( 'hamista_flash_u' . $customer->ID );
		wp_set_current_user( $previous );
	}

	// Guests are keyed by their cookie token.
	$token                    = bin2hex( random_bytes( 16 ) );
	$_COOKIE['hamista_flash'] = $token;
	wp_set_current_user( 0 );
	try {
		hamista_flash( 'Guest message', 'warning' );
		assert_true( false !== get_transient( 'hamista_flash_g' . $token ) );
		assert_same( 'Guest message', hamista_flash_messages()[0]['message'] );
	} finally {
		unset( $_COOKIE['hamista_flash'] );
		delete_transient( 'hamista_flash_g' . $token );
		wp_set_current_user( $previous );
	}
}

function test_notify_normalizes_arguments_and_fires_the_action() {
	$events  = [];
	$capture = static function ( $user_id, $args ) use ( &$events ) {
		$events[] = [ $user_id, $args ];
	};
	add_action( 'hamista_notify', $capture, 10, 2 );
	try {
		hamista_notify(
			7,
			[
				'type'    => 'Ticket Reply!',
				'title'   => '<b>New reply</b>',
				'message' => "Line one\nLine two<script>x</script>",
				'link'    => 'javascript:alert(1)',
			]
		);
		hamista_notify( 0, [ 'title' => 'nobody' ] );
	} finally {
		remove_action( 'hamista_notify', $capture, 10 );
	}
	assert_same(
		[
			[
				7,
				[
					'type'    => 'ticketreply',
					'title'   => 'New reply',
					'message' => "Line one\nLine two",
					'link'    => '',
				],
			],
		],
		$events
	);
}

function test_multilingual_helpers_degrade_without_wpml_or_polylang() {
	assert_same( 'Hello', hamista_translate_string( 'Hello', 'greeting' ) );
	assert_same( '', hamista_language_switcher() );
}

function test_admin_screen_renders_for_administrators_over_http() {
	$cookies = hm_login( 'admin', 'admin' );
	$page    = hm_http( 'GET', '/wp-admin/admin.php?page=hamista', [ 'cookies' => $cookies ] );
	assert_same( 200, $page['status'] );
	assert_contains( 'Welcome to Hamista', $page['body'] );
	assert_same( 1, substr_count( $page['body'], 'class="hm-admin-header"' ), 'the landing page renders once' );
	assert_contains( '>Overview</a>', $page['body'], 'first submenu entry' );
	assert_same( 1, preg_match( '#<body class="[^"]*\bhamista-admin\b#', $page['body'] ), 'body class scopes the admin styles' );
	assert_contains( 'hamista-core/assets/admin/admin.css', $page['body'] );
	assert_contains( 'hamista-core/assets/js/ui.js', $page['body'], 'admin.js depends on the shared UI script' );
	assert_contains( 'toplevel_page_hamista', $page['body'] );
	assert_contains( 'data:image/svg+xml;base64,', $page['body'], 'menu icon' );

	$dashboard = hm_http( 'GET', '/wp-admin/', [ 'cookies' => $cookies ] );
	assert_false( str_contains( $dashboard['body'], 'hamista-core/assets/admin/admin.css' ), 'not loaded on other screens' );
}

function test_uninstall_keeps_data_unless_delete_data_is_on() {
	assert_false( (bool) hamista_get_option( 'hamista_core', 'delete_data' ) );
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		define( 'WP_UNINSTALL_PLUGIN', 'hamista-core/hamista-core.php' );
	}
	include HAMISTA_CORE_PATH . 'uninstall.php';
	assert_same( '1.0.0', get_option( 'hamista_core_version' ) );
	assert_true( get_role( 'administrator' )->has_cap( 'hamista_view_reports' ) );
}

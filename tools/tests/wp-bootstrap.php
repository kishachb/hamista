<?php
/**
 * HAMISTA integration test bootstrap, loaded by WP-CLI before WordPress:
 *
 *   $ENV/wp eval-file <file> --use-include --require=tools/tests/wp-bootstrap.php
 *
 * (tools/tests/run-integration.sh does this for every tests/integration/*.php.)
 *
 * Test files declare `test_*` functions, like unit tests. After WP-CLI has
 * included the file (WordPress fully loaded), every test function declared in
 * it runs; PASS/FAIL lines and a summary are printed, and the exit code is 1
 * when a test fails.
 *
 * A test also fails when, while it runs, PHP raises a notice/warning/deprecation
 * in Hamista code or WordPress reports `_doing_it_wrong()` / a deprecation.
 *
 * Helpers: the assertions from bootstrap.php, hm_http(), hm_login(), hm_wp_cli()
 * and hm_mu_plugin().
 *
 * @package Hamista\Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require_once __DIR__ . '/bootstrap.php';

if ( ! function_exists( 'hm_test_base_url' ) ) {
	/**
	 * Base URL of the local test server.
	 *
	 * @return string
	 */
	function hm_test_base_url(): string {
		$url = getenv( 'HAMISTA_TEST_URL' );
		return rtrim( false === $url || '' === $url ? 'http://127.0.0.1:8080' : $url, '/' );
	}

	/**
	 * Performs a real HTTP request against the local server.
	 *
	 * Redirects are not followed unless `$args['redirection']` says so, so
	 * tests see the real status code.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Path such as '/my-account/', or an absolute URL.
	 * @param array  $args   wp_remote_request() arguments (body, headers, cookies, …).
	 * @return array{status:int,headers:array,body:string,cookies:array}
	 * @throws Hamista_Test_Failure When the request fails at the transport level.
	 */
	function hm_http( string $method, string $path, array $args = [] ): array {
		$url      = preg_match( '#^https?://#', $path ) ? $path : hm_test_base_url() . '/' . ltrim( $path, '/' );
		$response = wp_remote_request(
			$url,
			array_merge(
				[
					'method'      => strtoupper( $method ),
					'timeout'     => 30,
					'redirection' => 0,
				],
				$args
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new Hamista_Test_Failure( "HTTP {$method} {$url} failed: " . $response->get_error_message() );
		}
		$headers = wp_remote_retrieve_headers( $response );
		return [
			'status'  => (int) wp_remote_retrieve_response_code( $response ),
			'headers' => is_object( $headers ) && method_exists( $headers, 'getAll' ) ? $headers->getAll() : (array) $headers,
			'body'    => (string) wp_remote_retrieve_body( $response ),
			'cookies' => wp_remote_retrieve_cookies( $response ),
		];
	}

	/**
	 * Logs in through wp-login.php and returns the auth cookies for hm_http().
	 *
	 * @param string $user     User login.
	 * @param string $password Password.
	 * @return WP_Http_Cookie[]
	 * @throws Hamista_Test_Failure When the login is refused.
	 */
	function hm_login( string $user, string $password ): array {
		$response = hm_http(
			'POST',
			'/wp-login.php',
			[
				'body'    => [
					'log'         => $user,
					'pwd'         => $password,
					'wp-submit'   => 'Log In',
					'testcookie'  => '1',
					'redirect_to' => hm_test_base_url() . '/wp-admin/',
				],
				'cookies' => [
					new WP_Http_Cookie(
						[
							'name'  => 'wordpress_test_cookie',
							'value' => 'WP Cookie check',
						]
					),
				],
			]
		);
		if ( 302 !== $response['status'] || [] === $response['cookies'] ) {
			throw new Hamista_Test_Failure( "Login as {$user} failed (HTTP {$response['status']})." );
		}
		return $response['cookies'];
	}

	/**
	 * Runs a WP-CLI command in a fresh process (a full WordPress lifecycle).
	 *
	 * `$early_php` (without `<?php`) is loaded with --require before WordPress,
	 * so it can use WP_CLI::add_wp_hook() to hook actions that fire during boot.
	 *
	 * @param string $command   Command and arguments, shell-quoted, e.g. "eval 'echo 1;'".
	 * @param string $early_php Optional PHP code to load before WordPress.
	 * @return array{stdout:string,stderr:string,code:int}
	 */
	function hm_wp_cli( string $command, string $early_php = '' ): array {
		$file = '';
		if ( '' !== $early_php ) {
			$file = sys_get_temp_dir() . '/hm-early-' . bin2hex( random_bytes( 8 ) ) . '.php';
			file_put_contents( $file, "<?php\n" . $early_php );
			$command .= ' --require=' . escapeshellarg( $file );
		}
		try {
			$result = WP_CLI::runcommand(
				$command,
				[
					'launch'     => true,
					'return'     => 'all',
					'exit_error' => false,
				]
			);
		} finally {
			if ( '' !== $file ) {
				unlink( $file );
			}
		}
		return [
			'stdout' => (string) $result->stdout,
			'stderr' => (string) $result->stderr,
			'code'   => (int) $result->return_code,
		];
	}

	/**
	 * Writes a temporary must-use plugin, so code runs inside HTTP requests.
	 *
	 * Always delete the returned file in a `finally` block.
	 *
	 * @param string $php PHP code without the opening tag.
	 * @return string Absolute path of the file.
	 */
	function hm_mu_plugin( string $php ): string {
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
		}
		$file = WPMU_PLUGIN_DIR . '/hamista-itest-' . bin2hex( random_bytes( 6 ) ) . '.php';
		file_put_contents( $file, "<?php\n// Temporary fixture written by a Hamista integration test.\n" . $php );
		return $file;
	}

	/**
	 * Records PHP errors from Hamista code and WordPress misuse notices.
	 *
	 * @param bool $reset Return the recorded errors and clear the list.
	 * @return string[]
	 */
	function hm_test_errors( bool $reset = false ): array {
		static $errors = null;
		if ( null === $errors ) {
			$errors = [];
			$record = static function ( string $message ) use ( &$errors ): void {
				$errors[] = $message;
			};

			$previous = set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
				static function ( int $severity, string $message, string $file, int $line ) use ( $record, &$previous ) {
					$ours = 1 === preg_match( '#/(plugins/hamista-[a-z-]+|themes/hamista|tools/tests)/#', str_replace( '\\', '/', $file ) );
					if ( $ours && ( error_reporting() & $severity ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
						$record( "PHP error {$severity}: {$message} (" . basename( $file ) . ":{$line})" );
					}
					return $previous ? $previous( $severity, $message, $file, $line ) : false;
				}
			);

			add_action(
				'doing_it_wrong_run',
				static function ( $function_name, $message ) use ( $record ): void {
					$record( "_doing_it_wrong: {$function_name}: " . wp_strip_all_tags( (string) $message ) );
				},
				10,
				2
			);
			foreach ( [ 'deprecated_function_run', 'deprecated_argument_run', 'deprecated_hook_run', 'deprecated_file_included' ] as $hook ) {
				add_action(
					$hook,
					static function ( $name ) use ( $record, $hook ): void {
						$record( "{$hook}: {$name}" );
					}
				);
			}
		}
		if ( $reset ) {
			$recorded = $errors;
			$errors   = [];
			return $recorded;
		}
		return $errors;
	}

	/**
	 * Runs the test functions of the file WP-CLI just evaluated, then exits.
	 */
	function hm_run_integration_tests(): void {
		$runner = WP_CLI::get_runner();
		$file   = isset( $runner->arguments[1] ) ? realpath( (string) $runner->arguments[1] ) : false;
		if ( false === $file ) {
			fwrite( STDERR, "Could not determine the evaluated test file.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			exit( 1 );
		}

		$tests = hm_test_functions_in_file( $file );
		if ( [] === $tests ) {
			fwrite( STDERR, "No test_* functions found in {$file}. Run it with --use-include.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			exit( 1 );
		}

		hm_test_errors( true );
		$started             = microtime( true );
		[ $passed, $failed ] = hm_test_run_functions(
			$tests,
			basename( dirname( $file, 3 ) ) . '/' . basename( $file ),
			static function (): string {
				$errors = hm_test_errors( true );
				return [] === $errors ? '' : implode( "\n      ", $errors );
			}
		);

		printf(
			"\n%s — %d tests, %d passed, %d failed, %d assertions (%.2fs)\n",
			0 === $failed ? 'OK' : 'FAILURES',
			$passed + $failed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output.
			$passed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$failed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			hm_test_assertions(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			microtime( true ) - $started // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		exit( 0 === $failed ? 0 : 1 );
	}

	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_hook( 'after_invoke:eval-file', 'hm_run_integration_tests' );
	}
}

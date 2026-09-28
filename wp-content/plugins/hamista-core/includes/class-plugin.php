<?php
/**
 * Core plugin container.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core;

use Hamista\Core\Admin\Admin_Menu;
use Hamista\Core\Admin\Admin_UI;
use Hamista\Core\Components\Renderer;
use Hamista\Core\Modules\Module_Registry;
use Hamista\Core\Support\Account_Endpoints;
use Hamista\Core\Support\Assets;
use Hamista\Core\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * The core plugin: owns the shared services and wires the lifecycle.
 *
 * Timing:
 * - `plugins_loaded` priority 5: boot() registers the hooks below, then
 *   fires `hamista_core_loaded` with this instance.
 * - `init` priority 0: the `hamista-core` text domain loads.
 * - `init` priority 1: `hamista_register_modules` fires with the module
 *   registry, then every enabled module whose requirements are met is
 *   booted. **Modules boot on init:1**, after translations are available
 *   and before the default-priority `init` callbacks (post types, rewrite
 *   endpoints) run.
 * - `wp_enqueue_scripts` / `admin_enqueue_scripts` priority 5: the shared
 *   `hamista-ui` (and, in wp-admin, `hamista-admin`) handles are registered.
 * - `admin_menu` priority 9: the top-level Hamista menu.
 *
 * Satellite plugins boot on `plugins_loaded` priority 20 and reach this
 * instance through hamista_core().
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * The single instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Module registry.
	 *
	 * @var Module_Registry
	 */
	private Module_Registry $modules;

	/**
	 * Core asset helper.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * Component renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $components;

	/**
	 * My Account endpoint registry.
	 *
	 * @var Account_Endpoints
	 */
	private Account_Endpoints $account_endpoints;

	/**
	 * Whether boot() has run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Whether boot_modules() has run.
	 *
	 * @var bool
	 */
	private bool $modules_booted = false;

	/**
	 * Returns the single instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Creates the shared services. Hooks are added in boot().
	 */
	private function __construct() {
		$this->modules           = new Module_Registry();
		$this->assets            = new Assets( HAMISTA_CORE_URL, HAMISTA_CORE_PATH, HAMISTA_CORE_VERSION );
		$this->components        = new Renderer();
		$this->account_endpoints = new Account_Endpoints();
	}

	/**
	 * Registers the core hooks and fires `hamista_core_loaded`. Runs once.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		Options::register_defaults( 'hamista_core', self::option_defaults() );
		Installer::maybe_upgrade();

		add_action( 'init', [ $this, 'load_textdomain' ], 0 );
		add_action( 'init', [ $this, 'boot_modules' ], 1 );
		add_action( 'init', [ Installer::class, 'maybe_flush_rewrite_rules' ], 999 );
		add_action( 'woocommerce_installed', [ Installer::class, 'grant_capabilities' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 5 );

		if ( class_exists( 'WooCommerce' ) ) {
			$this->account_endpoints->register_hooks();
		}

		if ( is_admin() ) {
			( new Admin_Menu() )->register();
			( new Admin_UI() )->register();
		}

		/**
		 * Fires once hamista-core has booted (plugins_loaded priority 5).
		 *
		 * @since 1.0.0
		 *
		 * @param Plugin $core The core plugin.
		 */
		do_action( 'hamista_core_loaded', $this );
	}

	/**
	 * Module registry.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_Registry
	 */
	public function modules(): Module_Registry {
		return $this->modules;
	}

	/**
	 * Core asset helper (URLs and handles under hamista-core/).
	 *
	 * @since 1.0.0
	 *
	 * @return Assets
	 */
	public function assets(): Assets {
		return $this->assets;
	}

	/**
	 * Component renderer.
	 *
	 * @since 1.0.0
	 *
	 * @return Renderer
	 */
	public function components(): Renderer {
		return $this->components;
	}

	/**
	 * My Account endpoint registry.
	 *
	 * @since 1.0.0
	 *
	 * @return Account_Endpoints
	 */
	public function account_endpoints(): Account_Endpoints {
		return $this->account_endpoints;
	}

	/**
	 * Loads the plugin's translations. Hooked to `init` priority 0.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'hamista-core', false, dirname( plugin_basename( HAMISTA_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * Collects modules and boots the enabled ones. Hooked to `init` priority 1.
	 *
	 * @since 1.0.0
	 */
	public function boot_modules(): void {
		if ( $this->modules_booted ) {
			return;
		}
		$this->modules_booted = true;

		/**
		 * Fires when modules may be registered (init priority 1).
		 *
		 * @since 1.0.0
		 *
		 * @param Module_Registry $registry Call `$registry->add( $module )`.
		 */
		do_action( 'hamista_register_modules', $this->modules );

		$this->modules->boot_enabled();
	}

	/**
	 * Registers the shared asset handles. Hooked to `wp_enqueue_scripts`
	 * and `admin_enqueue_scripts` priority 5; nothing is enqueued here.
	 *
	 * @since 1.0.0
	 */
	public function register_assets(): void {
		// The Hamista theme registers an empty `hamista-ui` style because its
		// main.css already contains the primitives (ruling R2).
		if ( ! wp_style_is( 'hamista-ui', 'registered' ) ) {
			$this->assets->register_style( 'hamista-ui', 'assets/css/ui.css' );
		}

		if ( ! wp_script_is( 'hamista-ui', 'registered' ) ) {
			$this->assets->register_script( 'hamista-ui', 'assets/js/ui.js' );
			$config = [
				'i18n' => [
					'copied'     => __( 'Copied!', 'hamista-core' ),
					'copyFailed' => __( 'Could not copy. Please copy it manually.', 'hamista-core' ),
					'confirm'    => __( 'Are you sure?', 'hamista-core' ),
				],
			];
			wp_add_inline_script( 'hamista-ui', 'window.hamistaUI = ' . wp_json_encode( $config ) . ';', 'before' );
		}

		if ( is_admin() && ! wp_style_is( 'hamista-admin', 'registered' ) ) {
			$this->assets->register_style( 'hamista-admin', 'assets/admin/admin.css' );
			$this->assets->register_script( 'hamista-admin', 'assets/admin/admin.js', [ 'hamista-ui' ] );
		}
	}

	/**
	 * Untranslated defaults of the `hamista_core` option.
	 *
	 * The settings tabs (Task 1.2 onwards) build their fields on these keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function option_defaults(): array {
		return [
			'delete_data'     => false,
			'ip_header'       => 'REMOTE_ADDR',
			'email_logo'      => 0,
			'mail_from_name'  => '',
			'mail_from_email' => '',
			'calendar'        => 'jalali',
			'persian_digits'  => false,
		];
	}
}

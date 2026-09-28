<?php
/**
 * License manager plugin container.
 *
 * @package Hamista\License
 */

namespace Hamista\License;

use Hamista\Core\Support\Options;
use Hamista\License\Admin\Settings;
use Hamista\License\Data\Activation_Repository;
use Hamista\License\Data\Download_Log_Repository;
use Hamista\License\Data\License_Repository;
use Hamista\License\Data\Release_Repository;
use Hamista\License\Modules\Licenses_Module;
use Hamista\License\Modules\Licenses_WooCommerce_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the license manager's shared services and wires the lifecycle.
 *
 * Timing (spec §14): boots on `plugins_loaded` priority 20 (only when
 * hamista-core is active).
 *
 * - boot() registers the `hamista_licenses` option defaults, re-runs the
 *   installer when the DB version changed, registers the settings tab
 *   (guarded: the settings framework may not exist yet) and hooks core's
 *   `hamista_register_modules` action to add this plugin's two modules
 *   (`licenses`, `licenses-woocommerce`) to the registry.
 * - `init` priority 0: the `hamista-license-manager` text domain loads.
 * - `init` priority 1 (core's `Plugin::boot_modules()`): fires
 *   `hamista_register_modules`, then boots every enabled module whose
 *   requirements are met — including this plugin's, since it hooked the
 *   action during its own `plugins_loaded` priority 20 boot().
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
	 * License repository.
	 *
	 * @var License_Repository
	 */
	private License_Repository $licenses;

	/**
	 * Activation repository.
	 *
	 * @var Activation_Repository
	 */
	private Activation_Repository $activations;

	/**
	 * Release repository.
	 *
	 * @var Release_Repository
	 */
	private Release_Repository $releases;

	/**
	 * Download log repository.
	 *
	 * @var Download_Log_Repository
	 */
	private Download_Log_Repository $download_log;

	/**
	 * License service.
	 *
	 * @var License_Service
	 */
	private License_Service $service;

	/**
	 * Whether boot() has run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Whether register_modules() has added the modules to the registry.
	 *
	 * @var bool
	 */
	private bool $modules_registered = false;

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
		$this->licenses     = new License_Repository();
		$this->activations  = new Activation_Repository();
		$this->releases     = new Release_Repository();
		$this->download_log = new Download_Log_Repository();
		$this->service = new License_Service( $this->licenses, $this->activations );
	}

	/**
	 * Registers the plugin's hooks. Runs once.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		Options::register_defaults( 'hamista_licenses', self::option_defaults() );
		Installer::maybe_upgrade();

		add_action( 'init', [ $this, 'load_textdomain' ], 0 );
		add_action( 'hamista_register_modules', [ $this, 'register_modules' ] );
		add_action( 'woocommerce_installed', [ Installer::class, 'grant_capabilities' ] );
		add_action( 'hamista_register_settings', [ $this, 'register_settings' ] );
	}

	/**
	 * License repository.
	 *
	 * @since 1.0.0
	 *
	 * @return License_Repository
	 */
	public function licenses(): License_Repository {
		return $this->licenses;
	}

	/**
	 * Activation repository.
	 *
	 * @since 1.0.0
	 *
	 * @return Activation_Repository
	 */
	public function activations(): Activation_Repository {
		return $this->activations;
	}

	/**
	 * Release repository.
	 *
	 * @since 1.0.0
	 *
	 * @return Release_Repository
	 */
	public function releases(): Release_Repository {
		return $this->releases;
	}

	/**
	 * Download log repository.
	 *
	 * @since 1.0.0
	 *
	 * @return Download_Log_Repository
	 */
	public function download_log(): Download_Log_Repository {
		return $this->download_log;
	}

	/**
	 * The license service.
	 *
	 * @since 1.0.0
	 *
	 * @return License_Service
	 */
	public function service(): License_Service {
		return $this->service;
	}

	/**
	 * Loads the plugin's translations. Hooked to `init` priority 0.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'hamista-license-manager', false, dirname( plugin_basename( HAMISTA_LM_FILE ) ) . '/languages' );
	}

	/**
	 * Adds this plugin's modules to the registry. Hooked to core's
	 * `hamista_register_modules` action (fired from core's `init` priority 1,
	 * just before it calls `Module_Registry::boot_enabled()`).
	 *
	 * @since 1.0.0
	 *
	 * @param \Hamista\Core\Modules\Module_Registry $registry Core's module registry.
	 */
	public function register_modules( $registry ): void {
		if ( $this->modules_registered ) {
			return;
		}
		$this->modules_registered = true;

		$registry->add( new Licenses_Module() );
		$registry->add( new Licenses_WooCommerce_Module( $this->service ) );
	}

	/**
	 * Registers the `licenses` settings tab (spec §4.5).
	 *
	 * The settings registry is built concurrently; when it (or add_tab())
	 * is missing, this is a no-op so activation never fails.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $settings The settings registry, when the framework fired the action.
	 */
	public function register_settings( $settings ): void {
		if ( ! is_object( $settings ) || ! method_exists( $settings, 'add_tab' ) ) {
			return;
		}
		$settings->add_tab( Settings::tab() );
	}

	/**
	 * Untranslated defaults of the `hamista_licenses` option (spec §7, table).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function option_defaults(): array {
		return [
			'key_prefix'                => 'HMST',
			'default_activation_limit'  => 1,
			'default_validity_days'     => 365,
			'count_local_domains'       => false,
			'local_patterns'            => '',
			'issue_on'                  => 'processing',
			'revoke_on_refund'          => true,
			'revoke_on_cancel'          => true,
			'show_keys_in_emails'       => true,
			'api_enabled'               => true,
			'api_rate_limit'            => 60,
			'api_fail_limit'            => 10,
			'token_ttl_hours'           => 24,
			'sign_responses'            => true,
			'sendfile_mode'             => 'php',
			'accel_prefix'              => '/hamista-protected/',
			'allowed_extensions'        => 'zip',
			'max_upload_mb'             => 100,
			'notify_customers_on_release' => true,
			'delete_data'               => false,
		];
	}
}

<?php
/**
 * Dashboard plugin bootstrap.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard;

use Hamista\Dashboard\Modules\Dashboard_Shell_Module;
use Hamista\Dashboard\Modules\Invoices_Module;
use Hamista\Dashboard\Modules\Notifications_Module;
use Hamista\Dashboard\Modules\Tickets_Module;
use Hamista\Dashboard\Settings\Settings_Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin into hamista-core: option defaults, settings tab and modules.
 *
 * Boots on `plugins_loaded` priority 20 (after core, priority 5), only when
 * `hamista_core()` is available.
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
	 * Whether boot() has run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

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
	 * Registers the plugin's hooks. Runs once.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		hamista_register_option_defaults( 'hamista_dashboard', self::option_defaults() );

		Installer::maybe_upgrade();

		add_action( 'init', [ $this, 'load_textdomain' ], 0 );
		add_action( 'hamista_register_modules', [ $this, 'register_modules' ] );
		add_action( 'hamista_register_settings', [ Settings_Schema::class, 'register' ] );
	}

	/**
	 * Loads the plugin's translations. Hooked to `init` priority 0.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'hamista-customer-dashboard', false, dirname( plugin_basename( HAMISTA_DASHBOARD_FILE ) ) . '/languages' );
	}

	/**
	 * Registers the four modules on `hamista_register_modules` (init priority 1).
	 *
	 * Idempotent: a package's own `hamista_register_modules` firing (some
	 * satellites re-fire the shared hook after fetching core's registry) must
	 * not attempt to re-add an id that is already registered, which
	 * `Module_Registry::add()` rightly rejects with a `_doing_it_wrong()`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $registry Core's Module_Registry.
	 */
	public function register_modules( $registry ): void {
		if ( ! is_object( $registry ) || ! method_exists( $registry, 'add' ) || ! method_exists( $registry, 'get' ) ) {
			return;
		}
		foreach ( [ new Dashboard_Shell_Module(), new Notifications_Module(), new Tickets_Module(), new Invoices_Module() ] as $module ) {
			if ( null === $registry->get( $module->id() ) ) {
				$registry->add( $module );
			}
		}
	}

	/**
	 * Untranslated defaults of the `hamista_dashboard` option (spec's brief §2).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function option_defaults(): array {
		return [
			// Sections.
			'section_overview'             => true,
			'section_invoices'             => true,
			'section_tickets'              => true,
			'section_notifications'        => true,

			// Overview.
			'welcome_text'                 => '',
			'quick_links'                  => [],
			'show_updates_card'            => true,
			'show_recent_orders'           => true,
			'show_recent_notifications'    => true,

			// Profile.
			'profile_mobile'               => true,
			'profile_mobile_required'      => false,
			'profile_company'              => true,
			'profile_website'              => true,

			// Notifications.
			'notifications_retention_days' => 90,

			// Tickets (implemented in D2).
			'ticket_departments'           => [
				[
					'id'   => 'support',
					'name' => '',
				],
				[
					'id'   => 'sales',
					'name' => '',
				],
				[
					'id'   => 'billing',
					'name' => '',
				],
				[
					'id'   => 'technical',
					'name' => '',
				],
			],
			'ticket_priorities'            => true,
			'ticket_attachments'           => true,
			'ticket_attachment_types'      => 'jpg,jpeg,png,webp,pdf,zip,txt',
			'ticket_attachment_max_mb'     => 5,
			'ticket_autoclose_days'        => 7,
			'ticket_notify_admin'          => true,
			'ticket_notify_customer'       => true,
			'ticket_admin_emails'          => '',

			// Invoices (implemented in D3).
			'invoice_prefix'                => 'INV-',
			'seller_name'                   => '',
			'seller_economic_code'          => '',
			'seller_national_id'            => '',
			'seller_registration_no'        => '',
			'seller_address'                => '',
			'seller_postal_code'            => '',
			'seller_phone'                  => '',
			'invoice_logo'                  => 0,
			'invoice_footer_note'           => '',
			'invoice_statuses'              => [ 'processing', 'completed' ],

			// Data.
			'delete_data'                   => false,
		];
	}
}

<?php
/**
 * The "Customer Dashboard" settings tab (spec §4.5 and the task brief).
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the `dashboard` tab (group `platform`) to core's settings registry.
 *
 * Hooked to `hamista_register_settings`; core's Settings_Registry (task 1.2)
 * may not be loaded yet, so every call is guarded with `method_exists()`.
 *
 * @since 1.0.0
 */
final class Settings_Schema {

	/**
	 * Adds the tab. Hooked to `hamista_register_settings`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $settings Core's Settings_Registry.
	 */
	public static function register( $settings ): void {
		if ( ! is_object( $settings ) || ! method_exists( $settings, 'add_tab' ) ) {
			return;
		}

		$settings->add_tab(
			[
				'id'       => 'dashboard',
				'title'    => __( 'Customer Dashboard', 'hamista-customer-dashboard' ),
				'icon'     => 'layout-grid',
				'group'    => 'platform',
				'option'   => 'hamista_dashboard',
				'priority' => 40,
				'sections' => [
					self::section_sections(),
					self::section_overview(),
					self::section_profile(),
					self::section_notifications(),
					self::section_tickets(),
					self::section_invoices(),
					self::section_data(),
				],
			]
		);
	}

	/**
	 * The on/off toggles for each part of the dashboard.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_sections(): array {
		return [
			'id'          => 'sections',
			'title'       => __( 'Sections', 'hamista-customer-dashboard' ),
			'description' => __( 'Switch parts of the customer dashboard on or off.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'      => 'section_overview',
					'type'    => 'toggle',
					'label'   => __( 'Overview', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'section_invoices',
					'type'    => 'toggle',
					'label'   => __( 'Invoices', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'section_tickets',
					'type'    => 'toggle',
					'label'   => __( 'Support tickets', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'section_notifications',
					'type'    => 'toggle',
					'label'   => __( 'Notifications', 'hamista-customer-dashboard' ),
					'default' => true,
				],
			],
		];
	}

	/**
	 * Overview screen content.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_overview(): array {
		return [
			'id'          => 'overview',
			'title'       => __( 'Overview', 'hamista-customer-dashboard' ),
			'description' => __( 'The welcome header and quick actions on the My Account dashboard.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'          => 'welcome_text',
					'type'        => 'textarea',
					'label'       => __( 'Welcome text', 'hamista-customer-dashboard' ),
					'description' => __( 'Shown under the customer\'s name. Leave empty for the default greeting.', 'hamista-customer-dashboard' ),
					'default'     => '',
				],
				[
					'id'          => 'quick_links',
					'type'        => 'repeater',
					'label'       => __( 'Quick actions', 'hamista-customer-dashboard' ),
					'description' => __( 'Replaces the default quick actions (new ticket, shop, order a service).', 'hamista-customer-dashboard' ),
					'default'     => [],
					'fields'      => [
						[
							'id'    => 'label',
							'type'  => 'text',
							'label' => __( 'Label', 'hamista-customer-dashboard' ),
						],
						[
							'id'    => 'url',
							'type'  => 'url',
							'label' => __( 'URL', 'hamista-customer-dashboard' ),
						],
						[
							'id'    => 'icon',
							'type'  => 'text',
							'label' => __( 'Icon', 'hamista-customer-dashboard' ),
						],
					],
				],
				[
					'id'      => 'show_updates_card',
					'type'    => 'toggle',
					'label'   => __( 'Show the "updates available" card', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'show_recent_orders',
					'type'    => 'toggle',
					'label'   => __( 'Show recent orders', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'show_recent_notifications',
					'type'    => 'toggle',
					'label'   => __( 'Show recent notifications', 'hamista-customer-dashboard' ),
					'default' => true,
				],
			],
		];
	}

	/**
	 * Profile fields on edit-account.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_profile(): array {
		return [
			'id'          => 'profile',
			'title'       => __( 'Profile fields', 'hamista-customer-dashboard' ),
			'description' => __( 'Extra fields on the customer\'s "Profile" (edit-account) page.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'      => 'profile_mobile',
					'type'    => 'toggle',
					'label'   => __( 'Mobile phone field', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'          => 'profile_mobile_required',
					'type'        => 'toggle',
					'label'       => __( 'Mobile phone is required', 'hamista-customer-dashboard' ),
					'default'     => false,
					'show_if'     => [
						'field' => 'profile_mobile',
						'value' => true,
					],
				],
				[
					'id'      => 'profile_company',
					'type'    => 'toggle',
					'label'   => __( 'Company field', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'profile_website',
					'type'    => 'toggle',
					'label'   => __( 'Website field', 'hamista-customer-dashboard' ),
					'default' => true,
				],
			],
		];
	}

	/**
	 * Notification retention.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_notifications(): array {
		return [
			'id'          => 'notifications',
			'title'       => __( 'Notifications', 'hamista-customer-dashboard' ),
			'description' => __( 'How long read notifications are kept before the daily cleanup removes them.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'      => 'notifications_retention_days',
					'type'    => 'number',
					'label'   => __( 'Keep read notifications for (days)', 'hamista-customer-dashboard' ),
					'default' => 90,
					'min'     => 1,
					'max'     => 3650,
					'step'    => 1,
				],
			],
		];
	}

	/**
	 * Ticket settings, declared now; the tickets endpoint is built in task D2.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_tickets(): array {
		return [
			'id'          => 'tickets',
			'title'       => __( 'Support tickets', 'hamista-customer-dashboard' ),
			'description' => __( 'Departments, priorities, attachments and notifications for support tickets.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'          => 'ticket_departments',
					'type'        => 'repeater',
					'label'       => __( 'Departments', 'hamista-customer-dashboard' ),
					'default'     => [
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
					'fields'      => [
						[
							'id'    => 'id',
							'type'  => 'text',
							'label' => __( 'Id', 'hamista-customer-dashboard' ),
						],
						[
							'id'    => 'name',
							'type'  => 'text',
							'label' => __( 'Name', 'hamista-customer-dashboard' ),
						],
					],
				],
				[
					'id'      => 'ticket_priorities',
					'type'    => 'toggle',
					'label'   => __( 'Let customers set a priority', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'ticket_attachments',
					'type'    => 'toggle',
					'label'   => __( 'Allow attachments', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'ticket_attachment_types',
					'type'    => 'text',
					'label'   => __( 'Allowed attachment extensions', 'hamista-customer-dashboard' ),
					'default' => 'jpg,jpeg,png,webp,pdf,zip,txt',
					'show_if' => [
						'field' => 'ticket_attachments',
						'value' => true,
					],
				],
				[
					'id'      => 'ticket_attachment_max_mb',
					'type'    => 'number',
					'label'   => __( 'Maximum attachment size (MB)', 'hamista-customer-dashboard' ),
					'default' => 5,
					'min'     => 1,
					'max'     => 100,
					'step'    => 1,
					'show_if' => [
						'field' => 'ticket_attachments',
						'value' => true,
					],
				],
				[
					'id'      => 'ticket_autoclose_days',
					'type'    => 'number',
					'label'   => __( 'Auto-close answered tickets after (days)', 'hamista-customer-dashboard' ),
					'default' => 7,
					'min'     => 0,
					'max'     => 90,
					'step'    => 1,
				],
				[
					'id'      => 'ticket_notify_admin',
					'type'    => 'toggle',
					'label'   => __( 'Email staff on new tickets and replies', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'      => 'ticket_notify_customer',
					'type'    => 'toggle',
					'label'   => __( 'Email customers on staff replies', 'hamista-customer-dashboard' ),
					'default' => true,
				],
				[
					'id'          => 'ticket_admin_emails',
					'type'        => 'text',
					'label'       => __( 'Additional staff emails', 'hamista-customer-dashboard' ),
					'description' => __( 'Comma-separated. Leave empty to use the site admin email only.', 'hamista-customer-dashboard' ),
					'default'     => '',
				],
			],
		];
	}

	/**
	 * Invoice settings, declared now; the invoices endpoint is built in task D3.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_invoices(): array {
		return [
			'id'          => 'invoices',
			'title'       => __( 'Invoices', 'hamista-customer-dashboard' ),
			'description' => __( 'Seller details printed on the customer\'s Jalali invoices.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'      => 'invoice_prefix',
					'type'    => 'text',
					'label'   => __( 'Invoice number prefix', 'hamista-customer-dashboard' ),
					'default' => 'INV-',
				],
				[
					'id'      => 'seller_name',
					'type'    => 'text',
					'label'   => __( 'Seller legal name', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'seller_economic_code',
					'type'    => 'text',
					'label'   => __( 'Economic code', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'seller_national_id',
					'type'    => 'text',
					'label'   => __( 'National id', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'seller_registration_no',
					'type'    => 'text',
					'label'   => __( 'Registration number', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'seller_address',
					'type'    => 'textarea',
					'label'   => __( 'Address', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'seller_postal_code',
					'type'    => 'text',
					'label'   => __( 'Postal code', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'seller_phone',
					'type'    => 'tel',
					'label'   => __( 'Phone', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'invoice_logo',
					'type'    => 'image',
					'label'   => __( 'Invoice logo', 'hamista-customer-dashboard' ),
					'default' => 0,
				],
				[
					'id'      => 'invoice_footer_note',
					'type'    => 'textarea',
					'label'   => __( 'Footer note', 'hamista-customer-dashboard' ),
					'default' => '',
				],
				[
					'id'      => 'invoice_statuses',
					'type'    => 'multicheck',
					'label'   => __( 'Order statuses with an invoice', 'hamista-customer-dashboard' ),
					'default' => [ 'processing', 'completed' ],
					'options' => [
						'processing' => __( 'Processing', 'hamista-customer-dashboard' ),
						'completed'  => __( 'Completed', 'hamista-customer-dashboard' ),
						'on-hold'    => __( 'On hold', 'hamista-customer-dashboard' ),
						'refunded'   => __( 'Refunded', 'hamista-customer-dashboard' ),
					],
				],
			],
		];
	}

	/**
	 * Uninstall data behaviour.
	 *
	 * @return array<string, mixed>
	 */
	private static function section_data(): array {
		return [
			'id'          => 'data',
			'title'       => __( 'Data', 'hamista-customer-dashboard' ),
			'description' => __( 'What happens to dashboard data when the plugin is uninstalled.', 'hamista-customer-dashboard' ),
			'fields'      => [
				[
					'id'          => 'delete_data',
					'type'        => 'toggle',
					'label'       => __( 'Delete data on uninstall', 'hamista-customer-dashboard' ),
					'description' => __( 'Removes the settings, tickets, notifications and their tables when the plugin is uninstalled.', 'hamista-customer-dashboard' ),
					'default'     => false,
				],
			],
		];
	}
}

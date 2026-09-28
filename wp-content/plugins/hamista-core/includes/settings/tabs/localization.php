<?php
/**
 * Localization tab (option `hamista_core`).
 *
 * `calendar` and `persian_digits` are registered by Task A's foundation;
 * this tab only adds `jalali_in_admin` and `persian_digits_prices`.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'localization',
	'title'    => __( 'Localization', 'hamista-core' ),
	'icon'     => 'globe',
	'group'    => 'platform',
	'option'   => 'hamista_core',
	'priority' => 20,
	'sections' => [
		[
			'id'     => 'calendar',
			'title'  => __( 'Calendar & digits', 'hamista-core' ),
			'fields' => [
				[
					'id'      => 'calendar',
					'type'    => 'select',
					'label'   => __( 'Calendar', 'hamista-core' ),
					'options' => [
						'jalali'    => __( 'Jalali (Solar Hijri)', 'hamista-core' ),
						'gregorian' => __( 'Gregorian', 'hamista-core' ),
					],
				],
				[
					'id'      => 'jalali_in_admin',
					'type'    => 'toggle',
					'label'   => __( 'Use the Jalali calendar in wp-admin', 'hamista-core' ),
					'show_if' => [ 'field' => 'calendar', 'value' => 'jalali' ],
				],
				[
					'id'          => 'persian_digits',
					'type'        => 'toggle',
					'label'       => __( 'Persian digits', 'hamista-core' ),
					'description' => __( 'Applies to fa_* locales only.', 'hamista-core' ),
				],
				[
					'id'      => 'persian_digits_prices',
					'type'    => 'toggle',
					'label'   => __( 'Persian digits in prices', 'hamista-core' ),
					'show_if' => [ 'field' => 'persian_digits', 'value' => true ],
				],
			],
		],
	],
];

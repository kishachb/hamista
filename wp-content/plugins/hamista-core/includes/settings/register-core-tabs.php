<?php
/**
 * Registers core's own settings tabs and the `modules` field type.
 *
 * Each file in tabs/ returns a plain tab array (spec §4.5); this file wires
 * them onto `hamista_register_settings`, in wp-admin only.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'hamista_register_settings', 'hamista_core_register_settings_tabs', 10 );
add_filter( 'hamista_settings_field_types', 'hamista_core_settings_field_type_modules' );

// Custom Code tab: frontend output (spec §4.5's Custom code bullet).
add_action( 'wp_head', 'hamista_core_output_code_head', 99 );
add_action( 'wp_body_open', 'hamista_core_output_code_body_open' );
add_action( 'wp_footer', 'hamista_core_output_code_footer', 99 );
add_action( 'wp_enqueue_scripts', 'hamista_core_output_code_css', PHP_INT_MAX );

/**
 * Requires every core tab file and registers it.
 *
 * @param \Hamista\Core\Settings\Settings_Registry $registry Registry.
 */
function hamista_core_register_settings_tabs( \Hamista\Core\Settings\Settings_Registry $registry ): void {
	$dir = __DIR__ . '/tabs/';
	foreach ( [ 'modules', 'performance', 'security', 'seo', 'localization', 'integrations', 'custom-code', 'system-status', 'data' ] as $file ) {
		$tab = require $dir . $file . '.php';
		if ( is_array( $tab ) ) {
			$registry->add_tab( $tab );
		}
	}
}

/**
 * Adds the `modules` field type: a card per registered module, rendered
 * from the Module_Registry. It represents the whole flat `hamista_modules`
 * option (`id => bool`); Settings_Page special-cases its storage.
 *
 * @param array $types Registered field types.
 * @return array
 */
function hamista_core_settings_field_type_modules( array $types ): array {
	$types['modules'] = [
		'render'   => 'hamista_core_render_modules_field',
		'sanitize' => 'hamista_core_sanitize_modules_field',
	];
	return $types;
}

/**
 * Renders the modules field: one card per registered module.
 *
 * @param array  $field     Field definition (unused).
 * @param mixed  $value     Value (unused; the registry is read directly).
 * @param string $name_attr Base name attribute, e.g. `hamista_field[modules]`.
 */
function hamista_core_render_modules_field( array $field, $value, string $name_attr ): void {
	unset( $field, $value );
	$registry = \Hamista\Core\Plugin::instance()->modules();
	$modules  = $registry->all();

	if ( [] === $modules ) {
		echo '<div class="hm-empty"><p class="hm-empty__text">' . esc_html__( 'No modules are registered yet.', 'hamista-core' ) . '</p></div>';
		return;
	}

	echo '<div class="hm-modules-grid">';
	foreach ( $modules as $id => $module ) {
		$enabled  = $registry->is_enabled( $id );
		$met      = $registry->requirements_met( $module );
		$reason   = $met ? '' : $registry->unavailable_reason( $module );
		$field_id = $name_attr . '[' . $id . ']';

		echo '<div class="hm-card hm-module-card' . ( $met ? '' : ' hm-module-card--unavailable' ) . '">';
		echo '<div class="hm-module-card__head">';
		echo '<h3 class="hm-card__title">' . esc_html( $module->title() ) . '</h3>';
		echo '<span class="hm-badge hm-badge--neutral">' . esc_html( ucwords( str_replace( '-', ' ', $module->group() ) ) ) . '</span>';
		echo '</div>';
		echo '<p class="hm-card__text">' . esc_html( $module->description() ) . '</p>';
		if ( '' !== $reason ) {
			echo '<p class="hm-field__help hm-module-card__reason">' . esc_html( $reason ) . '</p>';
		}
		echo '<label class="hm-toggle' . ( $met ? '' : ' hm-toggle--disabled' ) . '">';
		echo '<input type="hidden" name="' . esc_attr( $field_id ) . '" value="0" />';
		echo '<input type="checkbox" name="' . esc_attr( $field_id ) . '" value="1"' . checked( $enabled, true, false ) . disabled( $met, false, false ) . ' />';
		echo '<span class="hm-toggle__track"><span class="hm-toggle__thumb"></span></span>';
		echo '<span class="hm-sr-only">' . /* translators: %s: module title. */ esc_html( sprintf( __( 'Enable %s', 'hamista-core' ), $module->title() ) ) . '</span>';
		echo '</label>';
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Sanitizes the modules field into `id => bool` for every registered module.
 *
 * @param array $field Field definition (unused).
 * @param mixed $raw   Raw submitted value (`id => '0'|'1'`).
 * @return array<string, bool>
 */
function hamista_core_sanitize_modules_field( array $field, $raw ): array {
	unset( $field );
	$raw = is_array( $raw ) ? $raw : [];
	$out = [];
	foreach ( \Hamista\Core\Plugin::instance()->modules()->all() as $id => $module ) {
		$out[ $id ] = array_key_exists( $id, $raw ) ? filter_var( $raw[ $id ], FILTER_VALIDATE_BOOLEAN ) : $module->default_enabled();
	}
	return $out;
}

/**
 * Prints `code_head` before `</head>`. Hooked to `wp_head` priority 99.
 */
function hamista_core_output_code_head(): void {
	$html = (string) hamista_get_option( 'hamista_core', 'code_head', '' );
	if ( '' !== trim( $html ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized on save (raw only with unfiltered_html, else wp_kses_post).
	}
}

/**
 * Prints `code_body_open` right after `<body>`.
 */
function hamista_core_output_code_body_open(): void {
	$html = (string) hamista_get_option( 'hamista_core', 'code_body_open', '' );
	if ( '' !== trim( $html ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized on save.
	}
}

/**
 * Prints `code_footer` before `</body>`. Hooked to `wp_footer` priority 99.
 */
function hamista_core_output_code_footer(): void {
	$html = (string) hamista_get_option( 'hamista_core', 'code_footer', '' );
	if ( '' !== trim( $html ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized on save.
	}
}

/**
 * Adds `code_css` as an inline style after the last enqueued stylesheet
 * (theme styles included), so it always wins the cascade.
 */
function hamista_core_output_code_css(): void {
	$css = (string) hamista_get_option( 'hamista_core', 'code_css', '' );
	if ( '' === trim( $css ) ) {
		return;
	}
	$queue  = wp_styles()->queue;
	$handle = [] !== $queue ? end( $queue ) : 'hamista-ui';
	if ( ! wp_style_is( $handle, 'registered' ) ) {
		$handle = 'hamista-ui';
	}
	wp_add_inline_style( $handle, $css );
}

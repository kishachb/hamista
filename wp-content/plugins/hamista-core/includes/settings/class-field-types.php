<?php
/**
 * Settings field rendering and sanitization.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Settings;

use Hamista\Core\Support\Html;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Renders and sanitizes every §4.5 field type.
 *
 * A field definition is an array with at least `id` and `type`. Other
 * recognised keys: `label`, `description`, `default`, `placeholder`,
 * `options` (array or callable, for select/radio/choice/multicheck),
 * `min`/`max`/`step` (number), `mode` (code: 'css'|'html'), `readonly`
 * (code), `fields` (repeater sub-schema), `max_rows` (repeater), `items`
 * (array or callable, for sortable), `rows` (textarea), `content` (html).
 *
 * `render()` escapes everything except the `html` type, whose `content` is
 * pre-built, already-escaped markup. Custom types can be added through the
 * `hamista_settings_field_types` filter: `[ 'render' => callable, 'sanitize' => callable ]`.
 *
 * @since 1.0.0
 */
final class Field_Types {

	/**
	 * Whether a field type is known (built-in or added through the filter).
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	public static function is_known( string $type ): bool {
		return isset( self::types()[ $type ] );
	}

	/**
	 * Every registered field type.
	 *
	 * Recomputed on each call (registration and rendering happen only in
	 * wp-admin), so a type added through the filter is available regardless
	 * of registration order within the same request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{render:callable, sanitize:callable}>
	 */
	public static function types(): array {
		$builtin = [
			'text'        => [ 'render' => [ self::class, 'render_text' ], 'sanitize' => [ self::class, 'sanitize_text' ] ],
			'tel'         => [ 'render' => [ self::class, 'render_text' ], 'sanitize' => [ self::class, 'sanitize_text' ] ],
			'textarea'    => [ 'render' => [ self::class, 'render_textarea' ], 'sanitize' => [ self::class, 'sanitize_textarea' ] ],
			'url'         => [ 'render' => [ self::class, 'render_text' ], 'sanitize' => [ self::class, 'sanitize_url' ] ],
			'email'       => [ 'render' => [ self::class, 'render_text' ], 'sanitize' => [ self::class, 'sanitize_email' ] ],
			'number'      => [ 'render' => [ self::class, 'render_number' ], 'sanitize' => [ self::class, 'sanitize_number' ] ],
			'toggle'      => [ 'render' => [ self::class, 'render_toggle' ], 'sanitize' => [ self::class, 'sanitize_toggle' ] ],
			'select'      => [ 'render' => [ self::class, 'render_select' ], 'sanitize' => [ self::class, 'sanitize_choice' ] ],
			'radio'       => [ 'render' => [ self::class, 'render_radio' ], 'sanitize' => [ self::class, 'sanitize_choice' ] ],
			'choice'      => [ 'render' => [ self::class, 'render_choice' ], 'sanitize' => [ self::class, 'sanitize_choice' ] ],
			'color'       => [ 'render' => [ self::class, 'render_color' ], 'sanitize' => [ self::class, 'sanitize_color' ] ],
			'image'       => [ 'render' => [ self::class, 'render_image' ], 'sanitize' => [ self::class, 'sanitize_image' ] ],
			'page'        => [ 'render' => [ self::class, 'render_page' ], 'sanitize' => [ self::class, 'sanitize_page' ] ],
			'code'        => [ 'render' => [ self::class, 'render_code' ], 'sanitize' => [ self::class, 'sanitize_code' ] ],
			'repeater'    => [ 'render' => [ self::class, 'render_repeater' ], 'sanitize' => [ self::class, 'sanitize_repeater' ] ],
			'sortable'    => [ 'render' => [ self::class, 'render_sortable' ], 'sanitize' => [ self::class, 'sanitize_sortable' ] ],
			'multicheck'  => [ 'render' => [ self::class, 'render_multicheck' ], 'sanitize' => [ self::class, 'sanitize_multicheck' ] ],
			'html'        => [ 'render' => [ self::class, 'render_html' ], 'sanitize' => [ self::class, 'sanitize_html' ] ],
		];

		/**
		 * Filters the registered settings field types.
		 *
		 * @since 1.0.0
		 *
		 * @param array $types type => [ 'render' => callable, 'sanitize' => callable ].
		 */
		return (array) apply_filters( 'hamista_settings_field_types', $builtin );
	}

	/**
	 * Renders a field's control. Escapes everything.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Current value.
	 * @param string $name_attr Base `name` attribute, e.g. `hamista_field[header_sticky]`.
	 */
	public static function render( array $field, $value, string $name_attr ): void {
		$type  = (string) ( $field['type'] ?? '' );
		$types = self::types();
		if ( ! isset( $types[ $type ]['render'] ) || ! is_callable( $types[ $type ]['render'] ) ) {
			return;
		}
		call_user_func( $types[ $type ]['render'], $field, $value, $name_attr );
	}

	/**
	 * Sanitizes a submitted value by the field's type.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field definition (its `default` should already be resolved).
	 * @param mixed $raw   Raw (unslashed) submitted value.
	 * @return mixed
	 */
	public static function sanitize( array $field, $raw ) {
		$type  = (string) ( $field['type'] ?? '' );
		$types = self::types();
		if ( ! isset( $types[ $type ]['sanitize'] ) || ! is_callable( $types[ $type ]['sanitize'] ) ) {
			return $raw;
		}
		return call_user_func( $types[ $type ]['sanitize'], $field, $raw );
	}

	/* --------------------------------------------------------------- render */

	/**
	 * Common attributes: id, name, class, placeholder, data-show-if.
	 *
	 * @param array  $field     Field definition.
	 * @param string $name_attr Name attribute.
	 * @param array  $extra     Extra attributes.
	 * @return array
	 */
	private static function base_attrs( array $field, string $name_attr, array $extra = [] ): array {
		$attrs = [
			'id'   => self::id_attr( $name_attr ),
			'name' => $name_attr,
		];
		if ( isset( $field['placeholder'] ) ) {
			$attrs['placeholder'] = (string) $field['placeholder'];
		}
		if ( ! empty( $field['show_if'] ) ) {
			$attrs['data-hm-show-if'] = (array) $field['show_if'];
		}
		return array_merge( $attrs, $extra );
	}

	/**
	 * A safe `id` attribute derived from a `name` attribute.
	 *
	 * @param string $name_attr Name attribute.
	 * @return string
	 */
	private static function id_attr( string $name_attr ): string {
		return 'hm-f-' . preg_replace( '/[^a-zA-Z0-9_-]+/', '-', $name_attr );
	}

	/**
	 * text / tel / url / email share one control.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_text( array $field, $value, string $name_attr ): void {
		$html_type = [ 'url' => 'url', 'email' => 'email', 'tel' => 'tel' ][ $field['type'] ?? '' ] ?? 'text';
		$attrs     = self::base_attrs(
			$field,
			$name_attr,
			[
				'type'  => $html_type,
				'class' => [ 'hm-input' ],
				'value' => (string) $value,
			]
		);
		echo '<input' . Html::attrs( $attrs ) . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Html::attrs() escapes.
	}

	/**
	 * Textarea.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_textarea( array $field, $value, string $name_attr ): void {
		$rows  = isset( $field['rows'] ) ? max( 2, (int) $field['rows'] ) : 4;
		$attrs = self::base_attrs( $field, $name_attr, [ 'class' => [ 'hm-textarea' ], 'rows' => $rows ] );
		echo '<textarea' . Html::attrs( $attrs ) . '>' . esc_textarea( (string) $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Number, with min/max/step attributes.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_number( array $field, $value, string $name_attr ): void {
		$extra = [ 'type' => 'number', 'class' => [ 'hm-input', 'hm-input--number' ], 'value' => is_numeric( $value ) ? $value + 0 : '' ];
		foreach ( [ 'min', 'max', 'step' ] as $key ) {
			if ( isset( $field[ $key ] ) ) {
				$extra[ $key ] = $field[ $key ];
			}
		}
		echo '<input' . Html::attrs( self::base_attrs( $field, $name_attr, $extra ) ) . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Toggle switch. A hidden `0` input precedes the checkbox so an unchecked
	 * toggle still submits a value.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_toggle( array $field, $value, string $name_attr ): void {
		$id       = self::id_attr( $name_attr );
		$checked  = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		$show_if  = ! empty( $field['show_if'] ) ? Html::attrs( [ 'data-hm-show-if' => (array) $field['show_if'] ] ) : '';
		echo '<input type="hidden" name="' . esc_attr( $name_attr ) . '" value="0" />';
		echo '<label class="hm-toggle"' . $show_if . '><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name_attr ) . '" value="1"' . checked( $checked, true, false ) . ' /><span class="hm-toggle__track"><span class="hm-toggle__thumb"></span></span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Select.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_select( array $field, $value, string $name_attr ): void {
		$attrs = self::base_attrs( $field, $name_attr, [ 'class' => [ 'hm-select' ] ] );
		echo '<select' . Html::attrs( $attrs ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( self::resolve_options( $field ) as $key => $option ) {
			$label = is_array( $option ) ? (string) ( $option['label'] ?? $key ) : (string) $option;
			echo '<option value="' . esc_attr( $key ) . '"' . selected( (string) $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Radio list.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_radio( array $field, $value, string $name_attr ): void {
		$show_if = ! empty( $field['show_if'] ) ? Html::attrs( [ 'data-hm-show-if' => (array) $field['show_if'] ] ) : '';
		echo '<div class="hm-radio-group"' . $show_if . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( self::resolve_options( $field ) as $key => $option ) {
			$label = is_array( $option ) ? (string) ( $option['label'] ?? $key ) : (string) $option;
			echo '<label class="hm-check"><input type="radio" name="' . esc_attr( $name_attr ) . '" value="' . esc_attr( $key ) . '"' . checked( (string) $value, $key, false ) . ' /> <span>' . esc_html( $label ) . '</span></label>';
		}
		echo '</div>';
	}

	/**
	 * Visual choice cards.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_choice( array $field, $value, string $name_attr ): void {
		$show_if = ! empty( $field['show_if'] ) ? Html::attrs( [ 'data-hm-show-if' => (array) $field['show_if'] ] ) : '';
		echo '<div class="hm-choice-group"' . $show_if . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( self::resolve_options( $field ) as $key => $option ) {
			$is_selected = ( (string) $value === (string) $key );
			$label       = is_array( $option ) ? (string) ( $option['label'] ?? $key ) : (string) $option;
			$description = is_array( $option ) ? (string) ( $option['description'] ?? '' ) : '';
			$icon        = is_array( $option ) && '' !== ( $option['icon'] ?? '' ) ? hamista_icon( (string) $option['icon'] ) : '';
			echo '<label class="hm-choice' . ( $is_selected ? ' is-selected' : '' ) . '"><input type="radio" class="hm-sr-only" name="' . esc_attr( $name_attr ) . '" value="' . esc_attr( $key ) . '"' . checked( $is_selected, true, false ) . ' />' . $icon . '<span class="hm-choice__label">' . esc_html( $label ) . '</span>' . ( '' === $description ? '' : '<span class="hm-choice__desc">' . esc_html( $description ) . '</span>' ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon is Icons::get() output.
		}
		echo '</div>';
	}

	/**
	 * Colour: a native colour input paired with a text input carrying the
	 * real value (so 8-digit alpha hex survives; JS keeps them in sync).
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_color( array $field, $value, string $name_attr ): void {
		$value  = is_string( $value ) && '' !== $value ? $value : '#000000';
		$native = 1 === preg_match( '/^#[0-9a-fA-F]{6}/', $value ) ? substr( $value, 0, 7 ) : '#000000';
		echo '<span class="hm-color-field" data-hm-color>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="color" class="hm-color-field__native" value="' . esc_attr( $native ) . '" tabindex="-1" aria-hidden="true" />';
		echo '<input type="text" class="hm-input hm-color-field__text" id="' . esc_attr( self::id_attr( $name_attr ) ) . '" name="' . esc_attr( $name_attr ) . '" value="' . esc_attr( $value ) . '" maxlength="9" spellcheck="false" />';
		echo '</span>';
	}

	/**
	 * Image: attachment id via wp.media, with a preview.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_image( array $field, $value, string $name_attr ): void {
		$id  = absint( $value );
		$src = $id > 0 ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		echo '<div class="hm-media-field" data-hm-media>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="hidden" class="hm-media-field__value" name="' . esc_attr( $name_attr ) . '" value="' . esc_attr( (string) $id ) . '" />';
		echo '<div class="hm-media-field__preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="" />' : '' ) . '</div>';
		echo '<div class="hm-media-field__actions">';
		echo '<button type="button" class="button hm-media-field__select">' . esc_html__( 'Select image', 'hamista-core' ) . '</button> ';
		echo '<button type="button" class="button hm-media-field__remove"' . ( $id > 0 ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'hamista-core' ) . '</button>';
		echo '</div></div>';
	}

	/**
	 * Page dropdown.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_page( array $field, $value, string $name_attr ): void {
		wp_dropdown_pages(
			[
				'name'              => $name_attr,
				'id'                => self::id_attr( $name_attr ),
				'class'             => 'hm-select',
				'selected'          => absint( $value ),
				'show_option_none'  => __( '— Select a page —', 'hamista-core' ),
				'option_none_value' => 0,
				'echo'              => 1,
			]
		);
	}

	/**
	 * Code (CSS or HTML) textarea. Read-only when `readonly` is set (the
	 * caller decides this, e.g. HTML without `unfiltered_html`).
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value.
	 * @param string $name_attr Name attribute.
	 */
	public static function render_code( array $field, $value, string $name_attr ): void {
		$readonly = ! empty( $field['readonly'] );
		$attrs    = self::base_attrs(
			$field,
			$name_attr,
			[
				'class'        => [ 'hm-input', 'hm-code' ],
				'rows'         => isset( $field['rows'] ) ? max( 2, (int) $field['rows'] ) : 8,
				'spellcheck'   => 'false',
				'data-hm-code' => true,
				'readonly'     => $readonly,
			]
		);
		if ( $readonly ) {
			echo '<p class="hm-field__notice hm-alert hm-alert--warning">' . esc_html__( 'Your account cannot save unfiltered HTML, so this field is shown for reference only.', 'hamista-core' ) . '</p>';
		}
		echo '<textarea' . Html::attrs( $attrs ) . '>' . esc_textarea( (string) $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Repeater: rendered rows plus a `<template>` row the JS clones.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value (list of row arrays).
	 * @param string $name_attr Name attribute.
	 */
	public static function render_repeater( array $field, $value, string $name_attr ): void {
		$rows       = is_array( $value ) ? array_values( $value ) : [];
		$sub_fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : [];
		$max_rows   = isset( $field['max_rows'] ) ? (int) $field['max_rows'] : 0;

		echo '<div class="hm-repeater" data-hm-repeater' . ( $max_rows > 0 ? ' data-hm-repeater-max="' . (int) $max_rows . '"' : '' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="hm-repeater__rows">';
		foreach ( $rows as $index => $row ) {
			self::render_repeater_row( $sub_fields, is_array( $row ) ? $row : [], $name_attr, (string) $index );
		}
		echo '</div>';
		echo '<template class="hm-repeater__template">';
		self::render_repeater_row( $sub_fields, [], $name_attr, '__INDEX__' );
		echo '</template>';
		echo '<button type="button" class="button hm-repeater__add">' . esc_html__( 'Add row', 'hamista-core' ) . '</button>';
		echo '</div>';
	}

	/**
	 * One repeater row.
	 *
	 * @param array  $sub_fields Sub-field schema.
	 * @param array  $row        Row values.
	 * @param string $name_attr  Repeater's base name attribute.
	 * @param string $index      Row index (or the `__INDEX__` placeholder).
	 */
	private static function render_repeater_row( array $sub_fields, array $row, string $name_attr, string $index ): void {
		$title_field = $sub_fields[0]['id'] ?? '';
		$title       = '' !== $title_field ? (string) ( $row[ $title_field ] ?? '' ) : '';
		echo '<div class="hm-repeater__row" data-hm-repeater-row>';
		echo '<div class="hm-repeater__row-head">';
		echo '<span class="hm-repeater__handle" aria-hidden="true">' . hamista_icon( 'grip-vertical' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<span class="hm-repeater__row-title">' . esc_html( $title ) . '</span>';
		echo '<button type="button" class="hm-repeater__move" data-hm-move="up" aria-label="' . esc_attr__( 'Move up', 'hamista-core' ) . '">' . hamista_icon( 'chevron-up' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<button type="button" class="hm-repeater__move" data-hm-move="down" aria-label="' . esc_attr__( 'Move down', 'hamista-core' ) . '">' . hamista_icon( 'chevron-down' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<button type="button" class="hm-repeater__toggle" data-hm-toggle-row aria-label="' . esc_attr__( 'Collapse', 'hamista-core' ) . '">' . hamista_icon( 'chevron-down' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<button type="button" class="hm-repeater__remove" aria-label="' . esc_attr__( 'Remove row', 'hamista-core' ) . '">' . hamista_icon( 'trash-2' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		echo '<div class="hm-repeater__row-body">';
		foreach ( $sub_fields as $sub ) {
			$sub_id = (string) ( $sub['id'] ?? '' );
			if ( '' === $sub_id ) {
				continue;
			}
			$sub_name = $name_attr . '[' . $index . '][' . $sub_id . ']';
			echo '<div class="hm-field hm-field--sub">';
			if ( ! empty( $sub['label'] ) ) {
				echo '<label class="hm-field__label" for="' . esc_attr( self::id_attr( $sub_name ) ) . '">' . esc_html( $sub['label'] ) . '</label>';
			}
			self::render( $sub, $row[ $sub_id ] ?? ( $sub['default'] ?? '' ), $sub_name );
			echo '</div>';
		}
		echo '</div></div>';
	}

	/**
	 * Sortable list of toggles.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value (list of `{id, enabled}`).
	 * @param string $name_attr Name attribute.
	 */
	public static function render_sortable( array $field, $value, string $name_attr ): void {
		$items = self::resolve_items( $field );
		$rows  = self::ordered_rows( $items, is_array( $value ) ? $value : [] );

		echo '<ul class="hm-sortable" data-hm-sortable>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( $rows as $index => $row ) {
			$id      = (string) $row['id'];
			$label   = (string) ( $items[ $id ]['label'] ?? $id );
			$enabled = ! empty( $row['enabled'] );
			echo '<li class="hm-sortable__row" data-hm-sortable-id="' . esc_attr( $id ) . '">';
			echo '<span class="hm-sortable__handle" aria-hidden="true">' . hamista_icon( 'grip-vertical' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<input type="hidden" name="' . esc_attr( $name_attr . '[' . $index . '][id]' ) . '" value="' . esc_attr( $id ) . '" />';
			echo '<label class="hm-toggle hm-toggle--sm"><input type="checkbox" name="' . esc_attr( $name_attr . '[' . $index . '][enabled]' ) . '" value="1"' . checked( $enabled, true, false ) . ' /><span class="hm-toggle__track"><span class="hm-toggle__thumb"></span></span></label>';
			echo '<span class="hm-sortable__label">' . esc_html( $label ) . '</span>';
			echo '<span class="hm-sortable__move">';
			echo '<button type="button" data-hm-move="up" aria-label="' . esc_attr__( 'Move up', 'hamista-core' ) . '">' . hamista_icon( 'chevron-up' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<button type="button" data-hm-move="down" aria-label="' . esc_attr__( 'Move down', 'hamista-core' ) . '">' . hamista_icon( 'chevron-down' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</span></li>';
		}
		echo '</ul>';
	}

	/**
	 * Multiple checkboxes.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Value (list of keys).
	 * @param string $name_attr Name attribute.
	 */
	public static function render_multicheck( array $field, $value, string $name_attr ): void {
		$value   = is_array( $value ) ? array_map( 'strval', $value ) : [];
		$show_if = ! empty( $field['show_if'] ) ? Html::attrs( [ 'data-hm-show-if' => (array) $field['show_if'] ] ) : '';
		echo '<div class="hm-check-group"' . $show_if . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( self::resolve_options( $field ) as $key => $option ) {
			$label = is_array( $option ) ? (string) ( $option['label'] ?? $key ) : (string) $option;
			echo '<label class="hm-check"><input type="checkbox" name="' . esc_attr( $name_attr ) . '[]" value="' . esc_attr( $key ) . '"' . checked( in_array( (string) $key, $value, true ), true, false ) . ' /> <span>' . esc_html( $label ) . '</span></label>';
		}
		echo '</div>';
	}

	/**
	 * Static info. `content` must already be safe, escaped HTML (string or
	 * a callable returning one) — this type has no user input.
	 *
	 * @param array  $field Field definition.
	 * @param mixed  $value Unused.
	 * @param string $name_attr Unused.
	 */
	public static function render_html( array $field, $value, string $name_attr ): void {
		unset( $value, $name_attr );
		$content = $field['content'] ?? '';
		if ( is_callable( $content ) ) {
			$content = call_user_func( $content );
		}
		echo '<div class="hm-field__html">' . (string) $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped by the field's own content builder.
	}

	/* ------------------------------------------------------------ sanitize */

	/**
	 * text / tel.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string
	 */
	public static function sanitize_text( array $field, $raw ): string {
		unset( $field );
		return sanitize_text_field( wp_unslash( is_scalar( $raw ) ? (string) $raw : '' ) );
	}

	/**
	 * textarea.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string
	 */
	public static function sanitize_textarea( array $field, $raw ): string {
		unset( $field );
		return sanitize_textarea_field( wp_unslash( is_scalar( $raw ) ? (string) $raw : '' ) );
	}

	/**
	 * url.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string
	 */
	public static function sanitize_url( array $field, $raw ): string {
		unset( $field );
		return esc_url_raw( wp_unslash( is_scalar( $raw ) ? (string) $raw : '' ) );
	}

	/**
	 * email.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string
	 */
	public static function sanitize_email( array $field, $raw ): string {
		unset( $field );
		return sanitize_email( wp_unslash( is_scalar( $raw ) ? (string) $raw : '' ) );
	}

	/**
	 * number: cast, clamp, then snap to step.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return int|float
	 */
	public static function sanitize_number( array $field, $raw ) {
		$value = is_numeric( $raw ) ? (float) $raw : (float) self::field_default( $field, 0 );
		if ( isset( $field['min'] ) && $value < (float) $field['min'] ) {
			$value = (float) $field['min'];
		}
		if ( isset( $field['max'] ) && $value > (float) $field['max'] ) {
			$value = (float) $field['max'];
		}
		if ( isset( $field['step'] ) && (float) $field['step'] > 0 ) {
			$step  = (float) $field['step'];
			$base  = isset( $field['min'] ) ? (float) $field['min'] : 0.0;
			$value = $base + round( ( $value - $base ) / $step ) * $step;
		}
		return floor( $value ) === $value ? (int) $value : $value;
	}

	/**
	 * toggle.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return bool
	 */
	public static function sanitize_toggle( array $field, $raw ): bool {
		unset( $field );
		return filter_var( $raw, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * select / radio / choice: must be a known option key, else the default.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return mixed
	 */
	public static function sanitize_choice( array $field, $raw ) {
		$options = self::resolve_options( $field );
		$key     = is_scalar( $raw ) ? (string) $raw : '';
		if ( '' !== $key && array_key_exists( $key, $options ) ) {
			return $key;
		}
		return self::field_default( $field, '' );
	}

	/**
	 * color: #RGB, #RRGGBB or #RRGGBBAA, else the default.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string
	 */
	public static function sanitize_color( array $field, $raw ): string {
		$value = is_scalar( $raw ) ? trim( wp_unslash( (string) $raw ) ) : '';
		if ( 1 === preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return strtolower( $value );
		}
		return (string) self::field_default( $field, '' );
	}

	/**
	 * image: absint, then must be an existing attachment.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return int
	 */
	public static function sanitize_image( array $field, $raw ): int {
		unset( $field );
		$id = absint( $raw );
		return ( $id > 0 && 'attachment' === get_post_type( $id ) ) ? $id : 0;
	}

	/**
	 * page: absint, then must be an existing page.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return int
	 */
	public static function sanitize_page( array $field, $raw ): int {
		unset( $field );
		$id = absint( $raw );
		return ( $id > 0 && 'page' === get_post_type( $id ) ) ? $id : 0;
	}

	/**
	 * code: `css` strips tags; `html` is raw for `unfiltered_html`, else `wp_kses_post`.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string
	 */
	public static function sanitize_code( array $field, $raw ): string {
		$value = is_scalar( $raw ) ? wp_unslash( (string) $raw ) : '';
		$mode  = (string) ( $field['mode'] ?? 'html' );
		if ( 'css' === $mode ) {
			return wp_strip_all_tags( $value );
		}
		return current_user_can( 'unfiltered_html' ) ? $value : wp_kses_post( $value );
	}

	/**
	 * repeater: each row sanitized by its sub-fields; unknown keys dropped;
	 * `max_rows` enforced.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return array
	 */
	public static function sanitize_repeater( array $field, $raw ): array {
		$sub_fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : [];
		$rows       = is_array( $raw ) ? array_values( $raw ) : [];
		$max        = isset( $field['max_rows'] ) ? (int) $field['max_rows'] : 0;
		if ( $max > 0 ) {
			$rows = array_slice( $rows, 0, $max );
		}
		$clean = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean_row = [];
			foreach ( $sub_fields as $sub ) {
				$sub_id = (string) ( $sub['id'] ?? '' );
				if ( '' === $sub_id ) {
					continue;
				}
				$sub          = $sub + [ 'default' => '' ];
				$clean_row[ $sub_id ] = self::sanitize( $sub, $row[ $sub_id ] ?? null );
			}
			$clean[] = $clean_row;
		}
		return $clean;
	}

	/**
	 * sortable: `{id, enabled}` restricted to known ids, given order kept;
	 * missing known ids are appended with their default enabled state.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return array<int, array{id:string, enabled:bool}>
	 */
	public static function sanitize_sortable( array $field, $raw ): array {
		$items = self::resolve_items( $field );
		$rows  = is_array( $raw ) ? $raw : [];
		$seen  = [];
		$out   = [];
		foreach ( $rows as $row ) {
			$id = is_array( $row ) && isset( $row['id'] ) && is_scalar( $row['id'] ) ? (string) $row['id'] : '';
			if ( '' === $id || ! isset( $items[ $id ] ) || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$out[]       = [
				'id'      => $id,
				'enabled' => filter_var( is_array( $row ) ? ( $row['enabled'] ?? false ) : false, FILTER_VALIDATE_BOOLEAN ),
			];
		}
		foreach ( $items as $id => $item ) {
			if ( ! isset( $seen[ $id ] ) ) {
				$out[] = [ 'id' => $id, 'enabled' => (bool) ( $item['default'] ?? true ) ];
			}
		}
		return $out;
	}

	/**
	 * multicheck: intersect with the known options.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return string[]
	 */
	public static function sanitize_multicheck( array $field, $raw ): array {
		$options = self::resolve_options( $field );
		$raw     = is_array( $raw ) ? $raw : [];
		$out     = [];
		foreach ( $raw as $key ) {
			$key = is_scalar( $key ) ? (string) $key : '';
			if ( '' !== $key && array_key_exists( $key, $options ) && ! in_array( $key, $out, true ) ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	/**
	 * html: nothing to save.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value.
	 * @return null
	 */
	public static function sanitize_html( array $field, $raw ) {
		unset( $field, $raw );
		return null;
	}

	/* --------------------------------------------------------------- utils */

	/**
	 * `options` resolved to `key => label|array`.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field definition.
	 * @return array<string, mixed>
	 */
	public static function resolve_options( array $field ): array {
		$options = $field['options'] ?? [];
		if ( is_callable( $options ) ) {
			$options = call_user_func( $options );
		}
		$out = [];
		foreach ( (array) $options as $key => $value ) {
			$out[ (string) $key ] = $value;
		}
		return $out;
	}

	/**
	 * `items` resolved to `id => { label, default }` (for `sortable`).
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field definition.
	 * @return array<string, array{label:string, default:bool}>
	 */
	public static function resolve_items( array $field ): array {
		$items = $field['items'] ?? [];
		if ( is_callable( $items ) ) {
			$items = call_user_func( $items );
		}
		$out = [];
		foreach ( (array) $items as $key => $value ) {
			$id         = (string) $key;
			$out[ $id ] = is_array( $value )
				? [ 'label' => (string) ( $value['label'] ?? $id ), 'default' => (bool) ( $value['default'] ?? true ) ]
				: [ 'label' => (string) $value, 'default' => true ];
		}
		return $out;
	}

	/**
	 * Current rows in a valid, complete order: known saved rows first (in
	 * their saved order), then any known item missing from the saved value.
	 *
	 * @param array<string, array{label:string, default:bool}> $items Known items.
	 * @param array                                             $value Saved rows.
	 * @return array<int, array{id:string, enabled:bool}>
	 */
	private static function ordered_rows( array $items, array $value ): array {
		$seen = [];
		$out  = [];
		foreach ( $value as $row ) {
			$id = is_array( $row ) && isset( $row['id'] ) ? (string) $row['id'] : '';
			if ( '' === $id || ! isset( $items[ $id ] ) || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$out[]       = [ 'id' => $id, 'enabled' => (bool) ( $row['enabled'] ?? false ) ];
		}
		foreach ( $items as $id => $item ) {
			if ( ! isset( $seen[ $id ] ) ) {
				$out[] = [ 'id' => $id, 'enabled' => (bool) $item['default'] ];
			}
		}
		return $out;
	}

	/**
	 * A field's resolved default, or `$fallback`.
	 *
	 * @param array $field    Field definition.
	 * @param mixed $fallback Fallback.
	 * @return mixed
	 */
	private static function field_default( array $field, $fallback ) {
		return array_key_exists( 'default', $field ) ? $field['default'] : $fallback;
	}
}

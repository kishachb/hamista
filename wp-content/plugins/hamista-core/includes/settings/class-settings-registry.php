<?php
/**
 * Registry of settings tabs, sections and fields.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Settings;

use Hamista\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Holds the tree of settings tabs → sections → fields (spec §4.5).
 *
 * Built only in wp-admin, lazily, by `Plugin::settings()` firing
 * `hamista_register_settings`. Never build this on the frontend; frontend
 * reads go through `hamista_get_option()` and the registered option
 * defaults (Task A's `Options` class), which do not need the registry.
 *
 * A tab: `{ id, title, icon, group ('theme'|'platform'|'advanced'), option,
 * priority, sections: [ section, ... ] }`. A section: `{ id, title,
 * description, option? (overrides the tab's option), fields: [ field, ... ] }`.
 * `add_section()` can arrive before its tab is registered; the section is
 * queued until `add_tab()` is called for that id.
 *
 * @since 1.0.0
 */
final class Settings_Registry {

	/**
	 * Tabs by id. Each tab's `sections` is itself keyed by section id, and
	 * each section's `fields` is keyed by field id, all in insertion order.
	 *
	 * @var array<string, array>
	 */
	private array $tabs = [];

	/**
	 * Sections added before their tab: tab_id => list of section arrays
	 * (each still carrying its raw `fields` list).
	 *
	 * @var array<string, array<int, array>>
	 */
	private array $pending_sections = [];

	/**
	 * Field ids already registered per option, to catch duplicates.
	 *
	 * @var array<string, array<string, true>>
	 */
	private array $field_ids_by_option = [];

	/**
	 * Registers a tab (with its sections and fields, if given inline).
	 *
	 * @since 1.0.0
	 *
	 * @param array $tab Tab definition.
	 */
	public function add_tab( array $tab ): void {
		$id = isset( $tab['id'] ) ? sanitize_key( (string) $tab['id'] ) : '';
		if ( '' === $id ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'A settings tab needs an id.', 'hamista-core' ), '1.0.0' );
			return;
		}

		$tab += [
			'title'    => '',
			'icon'     => '',
			'group'    => 'advanced',
			'option'   => '',
			'priority' => 10,
			'sections' => [],
		];
		$tab['id'] = $id;

		$sections        = is_array( $tab['sections'] ) ? $tab['sections'] : [];
		$tab['sections'] = $this->tabs[ $id ]['sections'] ?? [];
		$this->tabs[ $id ] = $tab;

		foreach ( $sections as $section ) {
			if ( is_array( $section ) ) {
				$this->add_section( $id, $section );
			}
		}
		foreach ( $this->pending_sections[ $id ] ?? [] as $section ) {
			$this->add_section( $id, $section );
		}
		unset( $this->pending_sections[ $id ] );
	}

	/**
	 * Appends a section to a tab. Queued when the tab does not exist yet.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab_id  Tab id.
	 * @param array  $section Section definition.
	 */
	public function add_section( string $tab_id, array $section ): void {
		$tab_id = sanitize_key( $tab_id );
		$id     = isset( $section['id'] ) ? sanitize_key( (string) $section['id'] ) : '';
		if ( '' === $id ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'A settings section needs an id.', 'hamista-core' ), '1.0.0' );
			return;
		}

		if ( ! isset( $this->tabs[ $tab_id ] ) ) {
			$this->pending_sections[ $tab_id ][] = array_merge( $section, [ 'id' => $id ] );
			return;
		}

		$fields           = isset( $section['fields'] ) && is_array( $section['fields'] ) ? $section['fields'] : [];
		$section['id']    = $id;
		$section['fields'] = $this->tabs[ $tab_id ]['sections'][ $id ]['fields'] ?? [];
		$this->tabs[ $tab_id ]['sections'][ $id ] = $section;

		foreach ( $fields as $field ) {
			if ( is_array( $field ) ) {
				$this->add_field( $tab_id, $id, $field );
			}
		}
	}

	/**
	 * Adds a field to an existing section.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab_id     Tab id.
	 * @param string $section_id Section id.
	 * @param array  $field      Field definition (`id`, `type`, …).
	 */
	public function add_field( string $tab_id, string $section_id, array $field ): void {
		$tab_id     = sanitize_key( $tab_id );
		$section_id = sanitize_key( $section_id );
		if ( ! isset( $this->tabs[ $tab_id ]['sections'][ $section_id ] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Unknown settings section "%s" in tab "%s".', $section_id, $tab_id ) ), '1.0.0' );
			return;
		}

		$id   = isset( $field['id'] ) ? sanitize_key( (string) $field['id'] ) : '';
		$type = isset( $field['type'] ) ? (string) $field['type'] : '';
		if ( '' === $id || '' === $type ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'A settings field needs an id and a type.', 'hamista-core' ), '1.0.0' );
			return;
		}
		if ( ! Field_Types::is_known( $type ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Unknown settings field type "%s" for field "%s".', $type, $id ) ), '1.0.0' );
			return;
		}

		$field['id'] = $id;
		$option      = (string) ( $this->tabs[ $tab_id ]['sections'][ $section_id ]['option'] ?? $this->tabs[ $tab_id ]['option'] ?? '' );
		if ( '' !== $option ) {
			if ( isset( $this->field_ids_by_option[ $option ][ $id ] ) ) {
				_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Duplicate settings field id "%s" for option "%s".', $id, $option ) ), '1.0.0' );
				return;
			}
			$this->field_ids_by_option[ $option ][ $id ] = true;
		}

		$this->tabs[ $tab_id ]['sections'][ $section_id ]['fields'][ $id ] = $field;
	}

	/**
	 * A registered tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Tab id.
	 * @return array|null
	 */
	public function get_tab( $id ): ?array {
		return $this->tabs[ sanitize_key( (string) $id ) ] ?? null;
	}

	/**
	 * Every tab, sorted by group order (theme, platform, advanced) then priority.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array>
	 */
	public function tabs(): array {
		$order = [ 'theme' => 0, 'platform' => 1, 'advanced' => 2 ];
		$tabs  = array_values( $this->tabs );
		usort(
			$tabs,
			static function ( array $a, array $b ) use ( $order ): int {
				$ga = $order[ $a['group'] ] ?? 99;
				$gb = $order[ $b['group'] ] ?? 99;
				return $ga !== $gb ? $ga <=> $gb : ( (int) $a['priority'] <=> (int) $b['priority'] );
			}
		);
		return $tabs;
	}

	/**
	 * Every registered field of one option, across every tab and section.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option Option name.
	 * @return array<int, array{tab_id:string, section_id:string, option:string, field:array}>
	 */
	public function fields_for_option( string $option ): array {
		$out = [];
		foreach ( $this->tabs as $tab ) {
			foreach ( $tab['sections'] ?? [] as $section ) {
				$section_option = (string) ( $section['option'] ?? $tab['option'] ?? '' );
				if ( $section_option !== $option ) {
					continue;
				}
				foreach ( $section['fields'] ?? [] as $field ) {
					$out[] = [
						'tab_id'     => $tab['id'],
						'section_id' => $section['id'],
						'option'     => $option,
						'field'      => $field,
					];
				}
			}
		}
		return $out;
	}

	/**
	 * A field's default: its own `default`, else the option's registered default.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option   Option name.
	 * @param string $field_id Field id.
	 * @return mixed null when neither is set.
	 */
	public function default_for( string $option, string $field_id ) {
		foreach ( $this->fields_for_option( $option ) as $entry ) {
			if ( $entry['field']['id'] === $field_id ) {
				if ( array_key_exists( 'default', $entry['field'] ) ) {
					return $entry['field']['default'];
				}
				break;
			}
		}
		$defaults = Options::defaults( $option );
		return array_key_exists( $field_id, $defaults ) ? $defaults[ $field_id ] : null;
	}
}

<?php
/**
 * Registry of modules.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Modules;

use Hamista\Core\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Holds modules, resolves their on/off state and requirements, and boots them.
 *
 * On/off state lives in the `hamista_modules` option (`id => bool`); a
 * module without a saved state uses default_enabled().
 *
 * @since 1.0.0
 */
final class Module_Registry {

	/**
	 * Registered modules by id, in registration order.
	 *
	 * @var array<string, Module>
	 */
	private array $modules = [];

	/**
	 * Ids of booted modules.
	 *
	 * @var array<string, true>
	 */
	private array $booted = [];

	/**
	 * Registers a module. A later module with the same id replaces the
	 * earlier one, unless that one has already booted.
	 *
	 * @since 1.0.0
	 *
	 * @param Module $module Module.
	 */
	public function add( Module $module ): void {
		$id = $module->id();
		if ( '' === $id || sanitize_key( $id ) !== $id ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Module id "%s" may only contain lower-case letters, digits, dashes and underscores.', $id ) ), '1.0.0' );
			return;
		}
		if ( isset( $this->booted[ $id ] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Module "%s" has already booted and cannot be replaced.', $id ) ), '1.0.0' );
			return;
		}
		$this->modules[ $id ] = $module;
	}

	/**
	 * Returns a registered module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Module id.
	 * @return Module|null
	 */
	public function get( string $id ): ?Module {
		return $this->modules[ $id ] ?? null;
	}

	/**
	 * All registered modules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Module> By id, in registration order.
	 */
	public function all(): array {
		return $this->modules;
	}

	/**
	 * Whether a module is switched on (saved toggle, else its default).
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Module id.
	 * @return bool False for unregistered ids.
	 */
	public function is_enabled( string $id ): bool {
		$module = $this->get( $id );
		if ( null === $module ) {
			return false;
		}
		$saved = Options::get( 'hamista_modules', $id );
		return null === $saved ? $module->default_enabled() : wp_validate_boolean( $saved );
	}

	/**
	 * Whether every requirement of a module is met. Unknown keys are not met.
	 *
	 * @since 1.0.0
	 *
	 * @param Module $module Module.
	 * @return bool
	 */
	public function requirements_met( Module $module ): bool {
		foreach ( $module->requires() as $requirement ) {
			if ( ! self::requirement_met( (string) $requirement ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether a module is registered, enabled and has its requirements met.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Module id.
	 * @return bool
	 */
	public function is_active( string $id ): bool {
		$module = $this->get( $id );
		return null !== $module && $this->is_enabled( $id ) && $this->requirements_met( $module );
	}

	/**
	 * Whether a module has booted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Module id.
	 * @return bool
	 */
	public function is_booted( string $id ): bool {
		return isset( $this->booted[ $id ] );
	}

	/**
	 * Explains why a module cannot run, e.g. "Requires WooCommerce and Elementor."
	 *
	 * Translated; for wp-admin only.
	 *
	 * @since 1.0.0
	 *
	 * @param Module $module Module.
	 * @return string Empty when every requirement is met.
	 */
	public function unavailable_reason( Module $module ): string {
		$missing = [];
		foreach ( $module->requires() as $requirement ) {
			if ( ! self::requirement_met( (string) $requirement ) ) {
				$missing[] = self::requirement_label( (string) $requirement );
			}
		}
		if ( [] === $missing ) {
			return '';
		}
		/* translators: %s: list of missing plugins or themes, e.g. "WooCommerce and Elementor". */
		return sprintf( __( 'Requires %s.', 'hamista-core' ), wp_sprintf( '%l', $missing ) );
	}

	/**
	 * Boots every enabled module whose requirements are met. Each module
	 * boots at most once, so calling this again is safe.
	 *
	 * @since 1.0.0
	 */
	public function boot_enabled(): void {
		foreach ( $this->modules as $id => $module ) {
			if ( isset( $this->booted[ $id ] ) || ! $this->is_enabled( $id ) || ! $this->requirements_met( $module ) ) {
				continue;
			}
			$this->booted[ $id ] = true;
			$module->boot();
		}
	}

	/**
	 * Checks one requirement key.
	 *
	 * @param string $key Requirement key.
	 * @return bool
	 */
	private static function requirement_met( string $key ): bool {
		switch ( $key ) {
			case 'woocommerce':
				return class_exists( 'WooCommerce' );
			case 'elementor':
				return (bool) did_action( 'elementor/loaded' );
			case 'rank-math':
				return defined( 'RANK_MATH_VERSION' );
			case 'hamista-theme':
				return 'hamista' === get_template();
			default:
				return false;
		}
	}

	/**
	 * Human-readable name of a requirement.
	 *
	 * @param string $key Requirement key.
	 * @return string
	 */
	private static function requirement_label( string $key ): string {
		switch ( $key ) {
			case 'woocommerce':
				return 'WooCommerce';
			case 'elementor':
				return 'Elementor';
			case 'rank-math':
				return 'Rank Math';
			case 'hamista-theme':
				return __( 'the Hamista theme', 'hamista-core' );
			default:
				/* translators: %s: requirement key. */
				return sprintf( __( 'an unknown component (%s)', 'hamista-core' ), $key );
		}
	}
}

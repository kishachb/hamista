<?php
/**
 * Module contract (spec §4.6).
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * A feature that can be switched on and off on the Modules settings tab.
 *
 * Register modules on `hamista_register_modules` (init priority 1). The
 * registry calls boot() on the same hook, only when the module is enabled
 * and its requirements are met. title() and description() may translate;
 * they are only called in wp-admin.
 *
 * @since 1.0.0
 */
interface Module {

	/**
	 * Unique id: lower-case letters, digits, `-` and `_` (the option key in `hamista_modules`).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Translated title, shown on the Modules tab.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function title(): string;

	/**
	 * Translated one-sentence description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function description(): string;

	/**
	 * Group on the Modules tab: content | commerce | integrations | performance | security | tools.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function group(): string;

	/**
	 * Whether the module is on before the site owner changes its toggle.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function default_enabled(): bool;

	/**
	 * Requirement keys, e.g. [ 'woocommerce', 'elementor' ]. Unmet ⇒ not booted, shown as unavailable.
	 *
	 * Known keys: woocommerce, elementor, rank-math, hamista-theme. Unknown keys are never met.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function requires(): array;

	/**
	 * Registers the module's hooks. Only called when enabled and requirements are met.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void;
}

<?php
/**
 * Base class with the common module defaults.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Module defaults: group 'content', enabled by default, no requirements.
 *
 * Subclasses implement id(), title(), description() and boot().
 *
 * @since 1.0.0
 */
abstract class Abstract_Module implements Module {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function group(): string {
		return 'content';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function default_enabled(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function requires(): array {
		return [];
	}
}

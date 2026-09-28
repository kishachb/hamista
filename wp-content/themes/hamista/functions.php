<?php
/**
 * Hamista theme bootstrap.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'HAMISTA_THEME_VERSION', '1.0.0' );
define( 'HAMISTA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'HAMISTA_THEME_URI', trailingslashit( get_template_directory_uri() ) );

require_once HAMISTA_THEME_DIR . 'inc/class-autoloader.php';
\Hamista\Theme\Autoloader::register( 'Hamista\\Theme\\', HAMISTA_THEME_DIR . 'inc' );

require_once HAMISTA_THEME_DIR . 'inc/template-tags.php';

\Hamista\Theme\Theme::instance()->boot();

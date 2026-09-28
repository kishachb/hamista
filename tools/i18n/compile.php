<?php
/**
 * Compiles gettext .po files into .mo and .l10n.php (WordPress 6.5+ PHP translation files)
 * without booting WordPress.
 *
 * Usage:
 *   php tools/i18n/compile.php --all            every wp-content/** /languages/*.po in the repository
 *   php tools/i18n/compile.php file.po [...]    the given files
 *
 * WordPress's POMO and WP_Translation_File classes are loaded from HAMISTA_WP_PATH, or else from the
 * local test environment. Fuzzy and untranslated entries are left out, as msgfmt does.
 *
 * @package Hamista
 */

// A CLI build tool, not WordPress runtime code: plain file functions are intended.
// phpcs:disable WordPress.WP.AlternativeFunctions

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

const HAMISTA_I18N_DEFAULT_WP = '/tmp/claude-0/-home-user-hamista/c5cfabc0-d2de-525b-9c2d-6b735d553134/scratchpad/env/wordpress';

$hamista_wp = (string) getenv( 'HAMISTA_WP_PATH' );
$hamista_wp = rtrim( '' !== $hamista_wp ? $hamista_wp : HAMISTA_I18N_DEFAULT_WP, '/' );

// compat.php polyfills newer PHP functions that POMO uses (array_last() on WordPress 7.x); it reads
// ABSPATH/WPINC only to load sodium_compat, so point them at the same install.
defined( 'ABSPATH' ) || define( 'ABSPATH', $hamista_wp . '/' );
defined( 'WPINC' ) || define( 'WPINC', 'wp-includes' );

$hamista_includes = array(
	'/wp-includes/compat.php',
	'/wp-includes/pomo/po.php',
	'/wp-includes/pomo/mo.php',
	'/wp-includes/l10n/class-wp-translation-file.php',
	'/wp-includes/l10n/class-wp-translation-file-mo.php',
	'/wp-includes/l10n/class-wp-translation-file-php.php',
);
foreach ( $hamista_includes as $hamista_include ) {
	if ( ! is_readable( $hamista_wp . $hamista_include ) ) {
		fwrite( STDERR, "ERROR  WordPress not found at {$hamista_wp} (set HAMISTA_WP_PATH)\n" );
		exit( 1 );
	}
	require_once $hamista_wp . $hamista_include;
}

/**
 * Lists every .po file under wp-content/** /languages/.
 *
 * @param string $root Repository root.
 * @return string[]
 */
function hamista_i18n_find_po_files( string $root ): array {
	$files = array();
	$base  = $root . '/wp-content';
	if ( ! is_dir( $base ) ) {
		return $files;
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS )
	);
	foreach ( $iterator as $file ) {
		$path = str_replace( '\\', '/', $file->getPathname() );
		if ( str_contains( $path, '/node_modules/' ) || str_contains( $path, '/vendor/' ) ) {
			continue;
		}
		if ( preg_match( '#/languages/[^/]+\.po$#', $path ) ) {
			$files[] = $path;
		}
	}
	sort( $files );
	return $files;
}

/**
 * Compiles one .po file. Returns an error message, or '' on success.
 *
 * @param string $po_file Path to the .po file.
 * @return string
 */
function hamista_i18n_compile( string $po_file ): string {
	if ( ! is_readable( $po_file ) || ! str_ends_with( $po_file, '.po' ) ) {
		return 'not a readable .po file';
	}
	$po = new PO();
	if ( ! $po->import_from_file( $po_file ) ) {
		return 'could not parse the .po file';
	}

	$mo = new MO();
	$mo->set_headers( $po->headers );
	$count = 0;
	foreach ( $po->entries as $entry ) {
		if ( in_array( 'fuzzy', (array) $entry->flags, true ) || ! array_filter( (array) $entry->translations ) ) {
			continue;
		}
		$mo->add_entry( $entry );
		++$count;
	}

	$base    = substr( $po_file, 0, -3 );
	$mo_file = $base . '.mo';
	if ( ! $mo->export_to_file( $mo_file ) ) {
		return "could not write {$mo_file}";
	}

	$php = WP_Translation_File::transform( $mo_file, 'php' );
	if ( false === $php || false === file_put_contents( $base . '.l10n.php', $php ) ) {
		return "could not write {$base}.l10n.php";
	}

	fwrite( STDOUT, sprintf( "i18n   %s -> .mo + .l10n.php (%d strings)\n", $po_file, $count ) );
	return '';
}

$hamista_args = array_slice( $argv, 1 );
if ( ! $hamista_args ) {
	fwrite( STDERR, "Usage: php tools/i18n/compile.php [--all | file.po ...]\n" );
	exit( 1 );
}

$hamista_files = in_array( '--all', $hamista_args, true )
	? hamista_i18n_find_po_files( dirname( __DIR__, 2 ) )
	: $hamista_args;

if ( ! $hamista_files ) {
	fwrite( STDOUT, "i18n   no .po files found\n" );
	exit( 0 );
}

$hamista_failed = 0;
foreach ( $hamista_files as $hamista_file ) {
	$hamista_error = hamista_i18n_compile( $hamista_file );
	if ( '' !== $hamista_error ) {
		fwrite( STDERR, "ERROR  {$hamista_file}: {$hamista_error}\n" );
		++$hamista_failed;
	}
}
exit( $hamista_failed ? 1 : 0 );

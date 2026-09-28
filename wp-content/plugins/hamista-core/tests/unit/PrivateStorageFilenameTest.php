<?php
/**
 * Unit tests for Private_Storage::is_blocked_filename() (pure, no WordPress).
 *
 * A name is blocked when any dot-separated segment is an executable or
 * server-config extension, when it is a dot-file, or when it is not a plain
 * file name at all (path separators, NUL/control bytes, stream syntax).
 *
 * @package Hamista\Core\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-private-storage.php';

use Hamista\Core\Support\Private_Storage;

function test_blocks_php_hidden_behind_a_second_extension() {
	assert_true( Private_Storage::is_blocked_filename( 'x.php.jpg' ) );
	assert_true( Private_Storage::is_blocked_filename( 'invoice.pdf.phtml.png' ) );
}

function test_blocks_server_config_dot_files() {
	assert_true( Private_Storage::is_blocked_filename( '.htaccess' ) );
	assert_true( Private_Storage::is_blocked_filename( '.htpasswd' ) );
	assert_true( Private_Storage::is_blocked_filename( '.user.ini' ) );
	assert_true( Private_Storage::is_blocked_filename( '.env' ), 'any dot-file' );
}

function test_blocks_extensions_case_insensitively() {
	assert_true( Private_Storage::is_blocked_filename( 'a.PhP' ) );
	assert_true( Private_Storage::is_blocked_filename( 'SHELL.PHAR' ) );
}

function test_blocks_every_listed_executable_extension() {
	$blocked = [ 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'htaccess', 'htpasswd', 'ini' ];
	foreach ( $blocked as $extension ) {
		assert_true( Private_Storage::is_blocked_filename( 'file.' . $extension ), $extension );
	}
}

function test_blocks_a_blocked_segment_in_any_position() {
	assert_true( Private_Storage::is_blocked_filename( 'php.ini' ) );
	assert_true( Private_Storage::is_blocked_filename( 'php.jpg' ), 'first segment counts too' );
}

function test_blocks_trailing_dots_and_spaces_that_windows_strips() {
	assert_true( Private_Storage::is_blocked_filename( 'x.php.' ) );
	assert_true( Private_Storage::is_blocked_filename( 'x.php ' ) );
	assert_true( Private_Storage::is_blocked_filename( 'x.php . ' ) );
}

function test_blocks_names_that_are_not_plain_file_names() {
	assert_true( Private_Storage::is_blocked_filename( '' ) );
	assert_true( Private_Storage::is_blocked_filename( '../x.jpg' ) );
	assert_true( Private_Storage::is_blocked_filename( 'a/b.jpg' ) );
	assert_true( Private_Storage::is_blocked_filename( 'a\\b.jpg' ) );
	assert_true( Private_Storage::is_blocked_filename( "x.php\0.jpg" ) );
	assert_true( Private_Storage::is_blocked_filename( "x\n.jpg" ) );
	assert_true( Private_Storage::is_blocked_filename( 'x.jpg::$DATA' ) );
}

function test_allows_ordinary_file_names() {
	$allowed = [ 'photo.jpg', 'report.final.pdf', 'archive.zip', 'my-php-notes.txt', 'shop.png', 'python-guide.pdf', 'Design.PSD', 'نمونه قرارداد.docx', 'logo.ai', 'README' ];
	foreach ( $allowed as $name ) {
		assert_false( Private_Storage::is_blocked_filename( $name ), $name );
	}
}

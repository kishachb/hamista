<?php
/**
 * HAMISTA unit test runner (plain PHP, no WordPress).
 *
 * Usage:
 *   php tools/tests/run.php               Runs every wp-content/** /tests/unit/*Test.php.
 *   php tools/tests/run.php <path> [...]  Runs the given test files, or the
 *                                         tests/unit/*Test.php files under the given directories.
 *
 * Exit code: 0 when every test passes, 1 otherwise.
 *
 * @package Hamista\Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require __DIR__ . '/bootstrap.php';

/**
 * Finds unit test files for a file or directory argument.
 *
 * @param string $target File or directory path.
 * @return string[] Absolute file paths.
 */
function hm_test_discover( string $target ): array {
	$path = realpath( $target );
	if ( false === $path ) {
		fwrite( STDERR, "No such file or directory: {$target}\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		exit( 1 );
	}
	if ( is_file( $path ) ) {
		return [ $path ];
	}

	$files    = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
			static function ( SplFileInfo $item ): bool {
				return ! in_array( $item->getFilename(), [ 'node_modules', 'vendor', '.git' ], true );
			}
		)
	);
	foreach ( $iterator as $item ) {
		$file = $item->getPathname();
		if ( str_ends_with( $file, 'Test.php' ) && str_contains( str_replace( '\\', '/', $file ), '/tests/unit/' ) ) {
			$files[] = $file;
		}
	}
	return $files;
}

// Notices and warnings raised by the code under test fail the test.
set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
	static function ( int $severity, string $message, string $file, int $line ): bool {
		if ( ! ( error_reporting() & $severity ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
			return false;
		}
		throw new ErrorException( $message, 0, $severity, $file, $line ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}
);

$hm_targets = array_slice( $argv, 1 );
if ( [] === $hm_targets ) {
	$hm_targets = [ dirname( __DIR__, 2 ) . '/wp-content' ];
}

$hm_files = [];
foreach ( $hm_targets as $hm_target ) {
	$hm_files = array_merge( $hm_files, hm_test_discover( $hm_target ) );
}
$hm_files = array_values( array_unique( $hm_files ) );
sort( $hm_files );

if ( [] === $hm_files ) {
	fwrite( STDERR, "No unit test files found.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}

$hm_started = microtime( true );
$hm_passed  = 0;
$hm_failed  = 0;
foreach ( $hm_files as $hm_file ) {
	$hm_label = basename( dirname( $hm_file, 3 ) ) . '/' . basename( $hm_file );
	try {
		require_once $hm_file;
	} catch ( Throwable $hm_error ) {
		// A file that cannot load fails as a whole; the other files still run.
		++$hm_failed;
		echo 'FAIL  ' . $hm_label . " :: (loading the file)\n      " . get_class( $hm_error ) . ': ' . $hm_error->getMessage() . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output.
		continue;
	}
	[ $hm_p, $hm_f ] = hm_test_run_functions( hm_test_functions_in_file( $hm_file ), $hm_label );
	$hm_passed      += $hm_p;
	$hm_failed      += $hm_f;
}

printf(
	"\n%s — %d tests, %d passed, %d failed, %d assertions (%.2fs)\n",
	0 === $hm_failed ? 'OK' : 'FAILURES',
	$hm_passed + $hm_failed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integers.
	$hm_passed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$hm_failed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	hm_test_assertions(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	microtime( true ) - $hm_started // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

exit( 0 === $hm_failed ? 0 : 1 );

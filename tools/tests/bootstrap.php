<?php
/**
 * HAMISTA micro test framework: assertions and the test-function runner.
 *
 * Shared by the unit runner (tools/tests/run.php, plain PHP, no WordPress)
 * and the integration bootstrap (tools/tests/wp-bootstrap.php, inside WP-CLI).
 *
 * Test files declare global functions named `test_*`. Each one passes when it
 * returns normally and fails when it throws (an assertion failure or any
 * other Throwable). PHP notices and warnings raised by the code under test
 * count as failures.
 *
 * @package Hamista\Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

if ( ! defined( 'HAMISTA_TESTS' ) ) {
	// Lets pure classes load without WordPress (see their ABSPATH guards).
	define( 'HAMISTA_TESTS', true );
}

if ( ! class_exists( 'Hamista_Test_Failure' ) ) {
	/**
	 * Thrown by the assertion helpers when an expectation is not met.
	 */
	class Hamista_Test_Failure extends RuntimeException {}
}

if ( ! function_exists( 'hm_test_export' ) ) {
	/**
	 * Renders a value for a failure message.
	 *
	 * @param mixed $value Any value.
	 * @return string
	 */
	function hm_test_export( $value ): string {
		if ( is_string( $value ) ) {
			$text = "'" . $value . "'";
		} elseif ( is_object( $value ) ) {
			$text = get_class( $value ) . ' object';
		} else {
			$text = preg_replace( '/\s+/', ' ', var_export( $value, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- test output.
		}
		return strlen( $text ) > 400 ? substr( $text, 0, 400 ) . '…' : $text;
	}

	/**
	 * Counts assertions for the summary line.
	 *
	 * @param bool $increment Whether to count one more assertion.
	 * @return int The number of assertions so far.
	 */
	function hm_test_assertions( bool $increment = false ): int {
		static $count = 0;
		if ( $increment ) {
			++$count;
		}
		return $count;
	}

	/**
	 * Throws a test failure.
	 *
	 * @param string $reason  What went wrong.
	 * @param string $message Optional context from the test.
	 * @throws Hamista_Test_Failure Always.
	 */
	function hm_test_fail( string $reason, string $message = '' ): never {
		throw new Hamista_Test_Failure( '' === $message ? $reason : $message . ' — ' . $reason );
	}

	/**
	 * Asserts that two values are identical (===).
	 *
	 * @param mixed  $expected Expected value.
	 * @param mixed  $actual   Actual value.
	 * @param string $message  Optional context.
	 */
	function assert_same( $expected, $actual, string $message = '' ): void {
		hm_test_assertions( true );
		if ( $expected !== $actual ) {
			hm_test_fail( 'expected ' . hm_test_export( $expected ) . ', got ' . hm_test_export( $actual ), $message );
		}
	}

	/**
	 * Asserts that a value is exactly true.
	 *
	 * @param mixed  $actual  Actual value.
	 * @param string $message Optional context.
	 */
	function assert_true( $actual, string $message = '' ): void {
		hm_test_assertions( true );
		if ( true !== $actual ) {
			hm_test_fail( 'expected true, got ' . hm_test_export( $actual ), $message );
		}
	}

	/**
	 * Asserts that a value is exactly false.
	 *
	 * @param mixed  $actual  Actual value.
	 * @param string $message Optional context.
	 */
	function assert_false( $actual, string $message = '' ): void {
		hm_test_assertions( true );
		if ( false !== $actual ) {
			hm_test_fail( 'expected false, got ' . hm_test_export( $actual ), $message );
		}
	}

	/**
	 * Asserts that a string contains a substring, or an array contains a value (strict).
	 *
	 * @param mixed        $needle   Substring or array value.
	 * @param string|array $haystack String or array to search.
	 * @param string       $message  Optional context.
	 */
	function assert_contains( $needle, $haystack, string $message = '' ): void {
		hm_test_assertions( true );
		if ( is_array( $haystack ) ) {
			$found = in_array( $needle, $haystack, true );
		} elseif ( is_string( $haystack ) ) {
			$found = str_contains( $haystack, (string) $needle );
		} else {
			hm_test_fail( 'haystack must be a string or an array, got ' . hm_test_export( $haystack ), $message );
		}
		if ( ! $found ) {
			hm_test_fail( 'failed asserting that ' . hm_test_export( $haystack ) . ' contains ' . hm_test_export( $needle ), $message );
		}
	}

	/**
	 * Asserts that a callable throws a Throwable of the given class.
	 *
	 * @param string   $expected_class Throwable class (or parent class / interface).
	 * @param callable $callback       Code expected to throw.
	 * @param string   $message        Optional context.
	 * @return Throwable The caught throwable, for further assertions.
	 */
	function assert_throws( string $expected_class, callable $callback, string $message = '' ): Throwable {
		hm_test_assertions( true );
		try {
			$callback();
		} catch ( Hamista_Test_Failure $failure ) {
			throw $failure;
		} catch ( Throwable $thrown ) {
			if ( $thrown instanceof $expected_class ) {
				return $thrown;
			}
			hm_test_fail( 'expected ' . $expected_class . ' to be thrown, got ' . get_class( $thrown ) . ': ' . $thrown->getMessage(), $message );
		}
		hm_test_fail( 'expected ' . $expected_class . ' to be thrown, nothing was thrown', $message );
	}

	/**
	 * Runs test functions and prints one PASS/FAIL line per test.
	 *
	 * @param string[] $functions   Function names to run, in order.
	 * @param string   $label       Prefix for each line (usually the file name).
	 * @param callable $after_each  Optional. Called after each test; returns a
	 *                              failure message, or '' when the test is clean.
	 * @return array{0:int,1:int} Passed and failed counts.
	 */
	function hm_test_run_functions( array $functions, string $label, ?callable $after_each = null ): array {
		$passed = 0;
		$failed = 0;
		foreach ( $functions as $function ) {
			$error = '';
			try {
				$function();
			} catch ( Throwable $thrown ) {
				$error = $thrown instanceof Hamista_Test_Failure
					? $thrown->getMessage()
					: get_class( $thrown ) . ': ' . $thrown->getMessage() . ' (' . basename( $thrown->getFile() ) . ':' . $thrown->getLine() . ')';
			}
			if ( '' === $error && null !== $after_each ) {
				$error = (string) $after_each( $function );
			}
			if ( '' === $error ) {
				++$passed;
				echo 'PASS  ' . $label . ' :: ' . $function . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output.
			} else {
				++$failed;
				echo 'FAIL  ' . $label . ' :: ' . $function . "\n      " . $error . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output.
			}
		}
		return [ $passed, $failed ];
	}

	/**
	 * Returns the test_* functions declared in a file, in declaration order.
	 *
	 * @param string $file Absolute path of a test file that has been loaded.
	 * @return string[]
	 */
	function hm_test_functions_in_file( string $file ): array {
		$file  = (string) realpath( $file );
		$found = [];
		foreach ( get_defined_functions()['user'] as $function ) {
			if ( ! str_starts_with( $function, 'test_' ) ) {
				continue;
			}
			$reflection = new ReflectionFunction( $function );
			if ( realpath( (string) $reflection->getFileName() ) === $file ) {
				$found[ $function ] = $reflection->getStartLine();
			}
		}
		asort( $found );
		return array_keys( $found );
	}
}

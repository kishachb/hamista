<?php
/**
 * Unit tests for Hamista\Core\Autoloader (spec §14 mapping rules).
 *
 * @package Hamista\Core\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/class-autoloader.php';

use Hamista\Core\Autoloader;

function test_autoloader_maps_a_class_to_its_wpcs_file_candidates() {
	assert_same(
		[
			'/base/modules/class-abstract-module.php',
			'/base/modules/interface-abstract-module.php',
			'/base/modules/trait-abstract-module.php',
		],
		Autoloader::candidates( 'Hamista\\Core\\Modules\\Abstract_Module', 'Hamista\\Core\\', '/base/' )
	);
}

function test_autoloader_maps_the_spec_example_settings_page() {
	$candidates = Autoloader::candidates( 'Hamista\\Core\\Admin\\Settings_Page', 'Hamista\\Core\\', '/base' );
	assert_same( '/base/admin/class-settings-page.php', $candidates[0] );
}

function test_autoloader_maps_classes_in_the_root_namespace() {
	$candidates = Autoloader::candidates( 'Hamista\\Core\\Plugin', 'Hamista\\Core', '/base/' );
	assert_same( '/base/class-plugin.php', $candidates[0] );
}

function test_autoloader_turns_underscores_in_namespace_segments_into_dashes() {
	$candidates = Autoloader::candidates( 'Hamista\\Core\\Admin_Tools\\Report_Table', 'Hamista\\Core\\', '/base/' );
	assert_same( '/base/admin-tools/class-report-table.php', $candidates[0] );
}

function test_autoloader_prefix_match_is_case_insensitive() {
	$candidates = Autoloader::candidates( 'hamista\\core\\support\\JALALI', 'Hamista\\Core\\', '/base/' );
	assert_same( '/base/support/class-jalali.php', $candidates[0] );
}

function test_autoloader_ignores_classes_outside_the_prefix() {
	assert_same( [], Autoloader::candidates( 'Hamista\\Theme\\Options', 'Hamista\\Core\\', '/base/' ) );
	assert_same( [], Autoloader::candidates( 'Hamista\\Corex\\Thing', 'Hamista\\Core\\', '/base/' ), 'prefix ends at a namespace boundary' );
	assert_same( [], Autoloader::candidates( 'Hamista\\Core', 'Hamista\\Core\\', '/base/' ) );
}

function test_autoloader_rejects_names_that_could_escape_the_base_directory() {
	assert_same( [], Autoloader::candidates( 'Hamista\\Core\\..\\..\\etc\\Passwd', 'Hamista\\Core\\', '/base/' ) );
	assert_same( [], Autoloader::candidates( 'Hamista\\Core\\Sub/Dir', 'Hamista\\Core\\', '/base/' ) );
}

function test_autoloader_loads_classes_interfaces_and_traits_from_disk() {
	$dir = sys_get_temp_dir() . '/hamista-autoload-' . bin2hex( random_bytes( 6 ) );
	mkdir( $dir . '/fixture-group', 0777, true );
	$files = [
		$dir . '/fixture-group/interface-fixture-contract.php' => "<?php\nnamespace Hamista\\AutoloadFixture\\Fixture_Group;\ninterface Fixture_Contract {}\n",
		$dir . '/fixture-group/trait-fixture-helper.php' => "<?php\nnamespace Hamista\\AutoloadFixture\\Fixture_Group;\ntrait Fixture_Helper {}\n",
		$dir . '/class-fixture-service.php'              => "<?php\nnamespace Hamista\\AutoloadFixture;\nfinal class Fixture_Service implements Fixture_Group\\Fixture_Contract { use Fixture_Group\\Fixture_Helper; }\n",
	];
	foreach ( $files as $path => $code ) {
		file_put_contents( $path, $code );
	}

	try {
		Autoloader::register( 'Hamista\\AutoloadFixture\\', $dir );
		assert_true( class_exists( 'Hamista\\AutoloadFixture\\Fixture_Service' ) );
		assert_true( interface_exists( 'Hamista\\AutoloadFixture\\Fixture_Group\\Fixture_Contract', false ), 'loaded as a dependency' );
		assert_true( trait_exists( 'Hamista\\AutoloadFixture\\Fixture_Group\\Fixture_Helper', false ) );
		assert_false( class_exists( 'Hamista\\AutoloadFixture\\Missing_Thing' ), 'missing files are not an error' );
	} finally {
		foreach ( array_keys( $files ) as $path ) {
			unlink( $path );
		}
		rmdir( $dir . '/fixture-group' );
		rmdir( $dir );
	}
}

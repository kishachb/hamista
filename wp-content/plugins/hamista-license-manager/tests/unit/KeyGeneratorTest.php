<?php
/**
 * Unit tests for Hamista\License\Support\Key_Generator (spec §7).
 *
 * @package Hamista\License\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-key-generator.php';

use Hamista\License\Support\Key_Generator;

/**
 * A generated key matches PREFIX-XXXXX-XXXXX-XXXXX-XXXXX with the default prefix.
 */
function test_generate_uses_the_default_prefix_and_shape() {
	$key = Key_Generator::generate();
	assert_true( 1 === preg_match( '/^HMST-[0-9A-HJKMNP-TV-Z]{5}-[0-9A-HJKMNP-TV-Z]{5}-[0-9A-HJKMNP-TV-Z]{5}-[0-9A-HJKMNP-TV-Z]{5}$/', $key ), $key );
}

/**
 * A custom prefix is honoured.
 */
function test_generate_uses_a_custom_prefix() {
	$key = Key_Generator::generate( 'ACME' );
	assert_true( str_starts_with( $key, 'ACME-' ), $key );
}

/**
 * Every character of the random groups belongs to the Crockford base32 alphabet
 * (no I, L, O, U).
 */
function test_generate_only_uses_the_crockford_alphabet() {
	$key    = Key_Generator::generate();
	$groups = explode( '-', $key );
	array_shift( $groups ); // Drop the prefix.
	foreach ( $groups as $group ) {
		assert_true( 5 === strlen( $group ), $group );
		for ( $i = 0; $i < strlen( $group ); $i++ ) {
			assert_true( str_contains( '0123456789ABCDEFGHJKMNPQRSTVWXYZ', $group[ $i ] ), 'unexpected character: ' . $group[ $i ] );
		}
	}
}

/**
 * 10,000 generated keys are all unique (100 bits of entropy makes a collision
 * astronomically unlikely).
 */
function test_generate_is_unique_over_ten_thousand_keys() {
	$seen = [];
	for ( $i = 0; $i < 10000; $i++ ) {
		$seen[ Key_Generator::generate() ] = true;
	}
	assert_same( 10000, count( $seen ) );
}

/**
 * is_well_formed() accepts a well-shaped key and rejects malformed ones.
 */
function test_is_well_formed_accepts_and_rejects() {
	assert_true( Key_Generator::is_well_formed( 'HMST-23456-789AB-CDEFG-HJKMN' ) );
	assert_false( Key_Generator::is_well_formed( 'HMST-23456-789AB-CDEFG-HJKM' ), 'group too short' );
	assert_false( Key_Generator::is_well_formed( 'HMST-23456-789AB-CDEFG-HJKMNX' ), 'group too long' );
	assert_false( Key_Generator::is_well_formed( 'hmst-23456-789AB-CDEFG-HJKMN' ), 'lower case prefix' );
	assert_false( Key_Generator::is_well_formed( 'HMST-23456-789AB-CDEFG-HJKMO' ), 'O is not in the alphabet' );
	assert_false( Key_Generator::is_well_formed( 'HMST_23456-789AB-CDEFG-HJKMN' ), 'wrong separator' );
	assert_false( Key_Generator::is_well_formed( '23456-789AB-CDEFG-HJKMN' ), 'missing prefix' );
	assert_false( Key_Generator::is_well_formed( '' ) );
}

/**
 * is_well_formed() accepts prefixes from 2 to 8 [A-Z0-9] characters and
 * rejects prefixes outside that range or with other characters.
 */
function test_is_well_formed_prefix_length_and_charset() {
	assert_true( Key_Generator::is_well_formed( 'AB-23456-789AB-CDEFG-HJKMN' ), 'two-char prefix' );
	assert_true( Key_Generator::is_well_formed( 'ABCDEFGH-23456-789AB-CDEFG-HJKMN' ), 'eight-char prefix' );
	assert_false( Key_Generator::is_well_formed( 'A-23456-789AB-CDEFG-HJKMN' ), 'one-char prefix' );
	assert_false( Key_Generator::is_well_formed( 'ABCDEFGHI-23456-789AB-CDEFG-HJKMN' ), 'nine-char prefix' );
	assert_false( Key_Generator::is_well_formed( 'AB1-2-23456-789AB-CDEFG-HJKMN' ), 'prefix with a dash' );
}

/**
 * normalize() trims, upper-cases, maps Crockford aliases and removes whitespace.
 */
function test_normalize_trims_uppercases_and_removes_whitespace() {
	assert_same( 'HMST-23456-789AB-CDEFG-HJKMN', Key_Generator::normalize( "  hmst-23456-789ab-cdefg-hjkmn  \n" ) );
	assert_same( 'HMST23456789AB', Key_Generator::normalize( "hmst 23456 789ab" ) );
}

/**
 * normalize() maps the Crockford aliases: O -> 0, I and L -> 1.
 */
function test_normalize_maps_crockford_aliases() {
	assert_same( 'HMST-01134-56789', Key_Generator::normalize( 'hmst-OiL34-56789' ) );
	assert_same( '011', Key_Generator::normalize( 'oil' ) );
	assert_same( '000111111', Key_Generator::normalize( 'oOOiIIlLL' ) );
}

/**
 * normalize() is idempotent on an already-normalised key.
 */
function test_normalize_is_idempotent() {
	$key = Key_Generator::generate();
	assert_same( $key, Key_Generator::normalize( $key ) );
}

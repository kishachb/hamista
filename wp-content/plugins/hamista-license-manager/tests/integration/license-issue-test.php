<?php
/**
 * Integration tests: WooCommerce issuing/revoking and License_Service
 * semantics (spec §7).
 *
 * Run: tools/tests/run-integration.sh wp-content/plugins/hamista-license-manager/tests/integration/license-issue-test.php
 *
 * Creates its own products, orders and licenses and deletes them all in the
 * last test (test_zz_lm_cleanup), whatever earlier tests did. Never resets
 * the database.
 *
 * @package Hamista\License\Tests
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! function_exists( 'hamista_lm_service' ) ) {
	echo "Run via WP-CLI with WooCommerce and hamista-license-manager active.\n";
	return;
}

/**
 * Stores a fixture value created by one test function, for later ones to
 * read (via hamista_lm_test_get_fixture()) and test_zz_lm_cleanup() to remove.
 *
 * @param string $key   Fixture key.
 * @param mixed  $value Value.
 */
function hamista_lm_test_set_fixture( string $key, $value ): void {
	$GLOBALS['hamista_lm_test_fixtures'][ $key ] = $value;
}

/**
 * Reads a fixture value.
 *
 * @param string $key Fixture key.
 * @return mixed
 */
function hamista_lm_test_get_fixture( string $key ) {
	return $GLOBALS['hamista_lm_test_fixtures'][ $key ] ?? null;
}

/**
 * Creates the customer, a licensed simple product (limit 2, validity 30
 * days) and a licensed variable product with two variations (limits 1 and 5).
 */
function test_00_lm_setup_fixtures() {
	$user = get_user_by( 'login', 'customer' );
	assert_true( false !== $user, 'the `customer` user from the test env fixtures must exist' );
	hamista_lm_test_set_fixture( 'user_id', $user->ID );

	// Simple product: activation limit 2, validity 30 days.
	$simple = new WC_Product_Simple();
	$simple->set_name( 'Hamista LM Test Plugin' );
	$simple->set_slug( 'hamista-lm-test-plugin-' . time() );
	$simple->set_regular_price( '10000' );
	$simple->set_status( 'publish' );
	$simple->update_meta_data( '_hamista_lm_enabled', 'yes' );
	$simple->update_meta_data( '_hamista_lm_activation_limit', '2' );
	$simple->update_meta_data( '_hamista_lm_validity_days', '30' );
	$simple->update_meta_data( '_hamista_lm_slug', 'hamista-lm-test-plugin' );
	$simple_id = $simple->save();
	hamista_lm_test_set_fixture( 'simple_product_id', $simple_id );

	// Variable product: two variations with activation limits 1 and 5.
	$variable = new WC_Product_Variable();
	$variable->set_name( 'Hamista LM Test Theme' );
	$variable->set_slug( 'hamista-lm-test-theme-' . time() );
	$variable->set_status( 'publish' );
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( 0 );
	$attribute->set_name( 'Tier' );
	$attribute->set_options( [ 'Single', 'Extended' ] );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	$variable->set_attributes( [ $attribute ] );
	$variable->update_meta_data( '_hamista_lm_enabled', 'yes' );
	$variable->update_meta_data( '_hamista_lm_validity_days', '365' );
	$variable_id = $variable->save();
	hamista_lm_test_set_fixture( 'variable_product_id', $variable_id );

	$variation_a = new WC_Product_Variation();
	$variation_a->set_parent_id( $variable_id );
	$variation_a->set_attributes( [ 'tier' => 'Single' ] );
	$variation_a->set_regular_price( '5000' );
	$variation_a->update_meta_data( '_hamista_lm_activation_limit', '1' );
	$variation_a_id = $variation_a->save();

	$variation_b = new WC_Product_Variation();
	$variation_b->set_parent_id( $variable_id );
	$variation_b->set_attributes( [ 'tier' => 'Extended' ] );
	$variation_b->set_regular_price( '9000' );
	$variation_b->update_meta_data( '_hamista_lm_activation_limit', '5' );
	$variation_b_id = $variation_b->save();

	hamista_lm_test_set_fixture( 'variation_a_id', $variation_a_id );
	hamista_lm_test_set_fixture( 'variation_b_id', $variation_b_id );

	assert_true( $simple_id > 0 && $variable_id > 0 && $variation_a_id > 0 && $variation_b_id > 0, 'every fixture product must save' );
}

/**
 * An order for `customer`, qty 2 of the simple product, set to processing:
 * issues 2 licenses with the product's activation limit and ~30-day validity.
 */
function test_01_lm_processing_issues_licenses_with_right_limits_and_expiry() {
	$user_id    = hamista_lm_test_get_fixture( 'user_id' );
	$product_id = hamista_lm_test_get_fixture( 'simple_product_id' );

	$order = wc_create_order( [ 'customer_id' => $user_id ] );
	$order->add_product( wc_get_product( $product_id ), 2 );
	$order->calculate_totals();
	$order->save();
	hamista_lm_test_set_fixture( 'simple_order_id', $order->get_id() );

	$order->update_status( 'processing' );
	$order = wc_get_order( $order->get_id() );

	$items = array_values( $order->get_items( 'line_item' ) );
	assert_same( 1, count( $items ), 'one line item' );
	$ids = $items[0]->get_meta( '_hamista_license_ids', true );
	assert_true( is_array( $ids ), 'license ids meta must be an array' );
	assert_same( 2, count( $ids ), 'qty 2 issues 2 licenses' );
	hamista_lm_test_set_fixture( 'simple_license_ids', $ids );

	$now = time();
	foreach ( $ids as $id ) {
		$license = hamista_lm_get_license( (int) $id );
		assert_true( null !== $license, 'the created license must be readable' );
		assert_same( 'active', $license['status'] );
		assert_same( 2, $license['activation_limit'], 'activation limit comes from the product meta' );
		assert_same( $user_id, $license['user_id'] );
		assert_same( $product_id, $license['product_id'] );

		$expires = strtotime( $license['expires_at'] . ' UTC' );
		$days    = round( ( $expires - $now ) / DAY_IN_SECONDS );
		assert_true( 29 <= $days && $days <= 31, "expiry should be ~30 days out, got {$days}" );
	}

	$notes = wc_get_order_notes( [ 'order_id' => $order->get_id() ] );
	assert_true( str_contains( implode( ' ', wp_list_pluck( $notes, 'content' ) ), 'Issued 2 licenses' ), 'an order note records the issue' );
}

/**
 * Completing the same order afterwards must not create duplicate licenses
 * (idempotent through the `_hamista_license_ids` item meta).
 */
function test_02_lm_completing_afterwards_issues_no_duplicates() {
	$order_id = hamista_lm_test_get_fixture( 'simple_order_id' );
	$order    = wc_get_order( $order_id );
	$order->update_status( 'completed' );

	$order = wc_get_order( $order_id );
	$items = array_values( $order->get_items( 'line_item' ) );
	$ids   = $items[0]->get_meta( '_hamista_license_ids', true );

	assert_same( hamista_lm_test_get_fixture( 'simple_license_ids' ), $ids, 'the same two license ids, not four' );

	global $wpdb;
	$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}hamista_licenses WHERE order_id = %d", $order_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
	assert_same( 2, $count, 'still exactly 2 license rows for this order' );
}

/**
 * A variable product's variations issue licenses with their own activation
 * limit overrides (1 and 5), not the product default.
 */
function test_03_lm_variation_limits_apply() {
	$user_id      = hamista_lm_test_get_fixture( 'user_id' );
	$variation_a  = hamista_lm_test_get_fixture( 'variation_a_id' );
	$variation_b  = hamista_lm_test_get_fixture( 'variation_b_id' );

	$order = wc_create_order( [ 'customer_id' => $user_id ] );
	$order->add_product( wc_get_product( $variation_a ), 1 );
	$order->add_product( wc_get_product( $variation_b ), 1 );
	$order->calculate_totals();
	$order->save();
	hamista_lm_test_set_fixture( 'variation_order_id', $order->get_id() );

	$order->update_status( 'processing' );
	$order = wc_get_order( $order->get_id() );

	$limits_by_variation = [];
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$ids = $item->get_meta( '_hamista_license_ids', true );
		assert_true( is_array( $ids ) && 1 === count( $ids ), 'one license per variation line item' );
		$license = hamista_lm_get_license( (int) $ids[0] );
		$limits_by_variation[ $item->get_variation_id() ] = $license['activation_limit'];
	}

	assert_same( 1, $limits_by_variation[ $variation_a ], 'variation A overrides the limit to 1' );
	assert_same( 5, $limits_by_variation[ $variation_b ], 'variation B overrides the limit to 5' );
}

/**
 * Refunding the order revokes its licenses.
 */
function test_04_lm_refund_revokes_licenses() {
	$order_id = hamista_lm_test_get_fixture( 'simple_order_id' );
	$order    = wc_get_order( $order_id );
	$order->update_status( 'refunded' );

	foreach ( hamista_lm_test_get_fixture( 'simple_license_ids' ) as $id ) {
		$license = hamista_lm_get_license( (int) $id );
		assert_same( 'revoked', $license['status'], "license {$id} must be revoked after a refund" );
	}
}

/**
 * A standalone license (limit 2) can activate two distinct real domains,
 * and a third is refused with activation_limit_reached.
 */
function test_05_lm_service_activate_up_to_the_limit_then_refuses() {
	$service = hamista_lm_service();
	$id      = hamista_lm()->licenses()->create(
		[
			'license_key'      => \Hamista\License\Support\Key_Generator::generate(),
			'product_id'       => hamista_lm_test_get_fixture( 'simple_product_id' ),
			'user_id'          => hamista_lm_test_get_fixture( 'user_id' ),
			'status'           => 'active',
			'activation_limit' => 2,
			'expires_at'       => null,
		]
	);
	hamista_lm_test_set_fixture( 'limit_license_id', $id );
	$key = hamista_lm_get_license( $id )['license_key'];

	$r1 = $service->activate( $key, 'https://alpha.example.com/' );
	assert_true( $r1->success );
	assert_same( 'activated', $r1->code );

	$r2 = $service->activate( $key, 'beta.example.com' );
	assert_true( $r2->success );
	assert_same( 'activated', $r2->code );
	assert_same( 2, $r2->data['license']['activation_count'] );

	$r3 = $service->activate( $key, 'gamma.example.com' );
	assert_false( $r3->success );
	assert_same( 'activation_limit_reached', $r3->code );
	assert_same( 403, $r3->http_status() );
}

/**
 * `staging.example.com` and `localhost` are local domains: they activate
 * even though the limit (2, both slots taken by the previous test) is reached.
 */
function test_06_lm_service_local_domains_do_not_count_against_the_limit() {
	$service = hamista_lm_service();
	$key     = hamista_lm_get_license( hamista_lm_test_get_fixture( 'limit_license_id' ) )['license_key'];

	$staging = $service->activate( $key, 'staging.example.com' );
	assert_true( $staging->success );
	assert_same( 'activated', $staging->code );

	$local = $service->activate( $key, 'localhost' );
	assert_true( $local->success );
	assert_same( 'activated', $local->code );

	// The two real domains still count as 2; local ones don't add to it.
	$license = hamista_lm_get_license( hamista_lm_test_get_fixture( 'limit_license_id' ) );
	assert_same( 4, $license['activation_count'], 'activation_count is a total display counter (2 real + 2 local)' );
}

/**
 * Deactivating a real domain frees a slot for a new one.
 */
function test_07_lm_service_deactivate_frees_a_slot() {
	$service = hamista_lm_service();
	$key     = hamista_lm_get_license( hamista_lm_test_get_fixture( 'limit_license_id' ) )['license_key'];

	$deactivated = $service->deactivate( $key, 'alpha.example.com' );
	assert_true( $deactivated->success );
	assert_same( 'deactivated', $deactivated->code );

	$activated = $service->activate( $key, 'delta.example.com' );
	assert_true( $activated->success, 'a slot freed by deactivation can be used by a new domain' );
	assert_same( 'activated', $activated->code );

	$missing = $service->deactivate( $key, 'never-activated.example.com' );
	assert_false( $missing->success );
	assert_same( 'not_activated', $missing->code );
}

/**
 * An expired license returns license_expired and its status flips to
 * 'expired' in the database.
 */
function test_08_lm_service_expired_license() {
	$service = hamista_lm_service();
	$id      = hamista_lm()->licenses()->create(
		[
			'license_key'      => \Hamista\License\Support\Key_Generator::generate(),
			'product_id'       => hamista_lm_test_get_fixture( 'simple_product_id' ),
			'user_id'          => hamista_lm_test_get_fixture( 'user_id' ),
			'status'           => 'active',
			'activation_limit' => 1,
			'expires_at'       => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
		]
	);
	hamista_lm_test_set_fixture( 'expired_license_id', $id );
	$key = hamista_lm_get_license( $id )['license_key'];

	$result = $service->activate( $key, 'expired-test.example.com' );
	assert_false( $result->success );
	assert_same( 'license_expired', $result->code );
	assert_same( 403, $result->http_status() );

	assert_same( 'expired', hamista_lm_get_license( $id )['status'], 'the status is flipped to expired as a side effect' );
}

/**
 * activate() with a ctx product_id that does not match the license's
 * product returns product_mismatch.
 */
function test_09_lm_service_product_mismatch() {
	$service       = hamista_lm_service();
	$simple_id     = hamista_lm_test_get_fixture( 'simple_product_id' );
	$other_product = hamista_lm_test_get_fixture( 'variable_product_id' );

	$id  = hamista_lm()->licenses()->create(
		[
			'license_key'      => \Hamista\License\Support\Key_Generator::generate(),
			'product_id'       => $simple_id,
			'user_id'          => hamista_lm_test_get_fixture( 'user_id' ),
			'status'           => 'active',
			'activation_limit' => 1,
			'expires_at'       => null,
		]
	);
	hamista_lm_test_set_fixture( 'mismatch_license_id', $id );
	$key = hamista_lm_get_license( $id )['license_key'];

	$mismatch = $service->activate( $key, 'mismatch-test.example.com', [ 'product_id' => $other_product ] );
	assert_false( $mismatch->success );
	assert_same( 'product_mismatch', $mismatch->code );
	assert_same( 400, $mismatch->http_status() );

	$match = $service->activate( $key, 'mismatch-test.example.com', [ 'product_id' => $simple_id ] );
	assert_true( $match->success, 'the correct product_id is accepted' );
}

/**
 * Activating an already-activated domain again returns ok `already_active`
 * (and touches the activation rather than erroring or duplicating it).
 */
function test_10_lm_service_already_active_reactivation() {
	$service = hamista_lm_service();
	$key     = hamista_lm_get_license( hamista_lm_test_get_fixture( 'mismatch_license_id' ) )['license_key'];

	$again = $service->activate( $key, 'mismatch-test.example.com' );
	assert_true( $again->success );
	assert_same( 'already_active', $again->code );

	$activations = hamista_lm()->activations()->for_license( hamista_lm_test_get_fixture( 'mismatch_license_id' ) );
	assert_same( 1, count( $activations ), 're-activating the same domain must not duplicate the row' );
}

/**
 * Deletes every fixture this file created. Runs last regardless of earlier
 * pass/fail (the runner continues past a failed test).
 */
function test_zz_lm_cleanup() {
	global $wpdb;

	foreach ( [ 'simple_order_id', 'variation_order_id' ] as $key ) {
		$order_id = hamista_lm_test_get_fixture( $key );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
		}
	}

	foreach ( [ 'variation_a_id', 'variation_b_id', 'variable_product_id', 'simple_product_id' ] as $key ) {
		$product_id = hamista_lm_test_get_fixture( $key );
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product instanceof WC_Product ) {
				$product->delete( true );
			}
		}
	}

	$license_ids = array_merge(
		(array) hamista_lm_test_get_fixture( 'simple_license_ids' ),
		array_filter(
			[
				hamista_lm_test_get_fixture( 'limit_license_id' ),
				hamista_lm_test_get_fixture( 'expired_license_id' ),
				hamista_lm_test_get_fixture( 'mismatch_license_id' ),
			]
		)
	);
	// Also collect the two licenses issued for the variation order (not stored under a single fixture key).
	$variable_id = hamista_lm_test_get_fixture( 'variable_product_id' );

	foreach ( array_unique( array_map( 'intval', $license_ids ) ) as $id ) {
		$wpdb->delete( $wpdb->prefix . 'hamista_license_activations', [ 'license_id' => $id ], [ '%d' ] );
		$wpdb->delete( $wpdb->prefix . 'hamista_licenses', [ 'id' => $id ], [ '%d' ] );
	}
	if ( $variable_id ) {
		$leftover = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}hamista_licenses WHERE product_id = %d", $variable_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $leftover as $id ) {
			$wpdb->delete( $wpdb->prefix . 'hamista_license_activations', [ 'license_id' => (int) $id ], [ '%d' ] );
			$wpdb->delete( $wpdb->prefix . 'hamista_licenses', [ 'id' => (int) $id ], [ '%d' ] );
		}
	}

	assert_true( true, 'cleanup finished' );
}

<?php
/**
 * License business logic: activation, deactivation, checks and WooCommerce
 * issuing/revoking (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License;

use Hamista\License\Data\Activation_Repository;
use Hamista\License\Data\License_Repository;
use Hamista\License\Support\Domain;
use Hamista\License\Support\Key_Generator;
use Hamista\License\Support\Result;

defined( 'ABSPATH' ) || exit;

/**
 * Every public method (except issue_for_order_item(), which returns the
 * created license ids) returns a Support\Result.
 *
 * Error codes: invalid_request, license_not_found, license_revoked,
 * license_inactive, license_expired, activation_limit_reached,
 * invalid_domain, not_activated, product_mismatch, rate_limited, api_disabled
 * (the last two are used by the REST layer added in LM2).
 *
 * @since 1.0.0
 */
final class License_Service {

	/**
	 * Statuses a license may be set to.
	 */
	public const STATUSES = [ 'active', 'inactive', 'expired', 'revoked' ];

	/**
	 * License repository.
	 *
	 * @var License_Repository
	 */
	private License_Repository $licenses;

	/**
	 * Activation repository.
	 *
	 * @var Activation_Repository
	 */
	private Activation_Repository $activations;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param License_Repository     $licenses    License repository.
	 * @param Activation_Repository $activations Activation repository.
	 */
	public function __construct( License_Repository $licenses, Activation_Repository $activations ) {
		$this->licenses    = $licenses;
		$this->activations = $activations;
	}

	/**
	 * Activates a license for a domain.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key    License key (any casing/spacing).
	 * @param string $domain URL, host or IP.
	 * @param array  $ctx    `product_id`, `slug`, `instance`, `version`, `ip`.
	 * @return Result
	 */
	public function activate( string $key, string $domain, array $ctx = [] ): Result {
		$license = $this->licenses->find_by_key( $key );
		if ( null === $license ) {
			return Result::error( 'license_not_found', __( 'This license key was not found.', 'hamista-license-manager' ), 404 );
		}

		$status_error = $this->check_status_and_expiry( $license );
		if ( null !== $status_error ) {
			return $status_error;
		}
		// check_status_and_expiry() may have expired the license; reload.
		$license = $this->licenses->find( $license['id'] ) ?? $license;
		if ( 'expired' === $license['status'] ) {
			return Result::error( 'license_expired', __( 'This license has expired.', 'hamista-license-manager' ), 403, [ 'license' => $this->payload( $license ) ] );
		}

		$mismatch = $this->product_mismatch( $license, $ctx );
		if ( $mismatch ) {
			return Result::error( 'product_mismatch', __( 'This license does not cover the requested product.', 'hamista-license-manager' ), 400 );
		}

		$normalized = Domain::normalize( $domain );
		if ( '' === $normalized ) {
			return Result::error( 'invalid_domain', __( 'The domain is not valid.', 'hamista-license-manager' ), 400 );
		}

		$existing = $this->activations->find( $license['id'], $normalized );
		if ( null !== $existing ) {
			$this->activations->touch( $existing['id'] );
			$license = $this->licenses->find( $license['id'] ) ?? $license;
			return Result::ok( 'already_active', __( 'This domain is already activated.', 'hamista-license-manager' ), [ 'license' => $this->payload( $license ) ] );
		}

		$is_local = Domain::is_local( $normalized, $this->local_patterns() );
		$count_local = (bool) hamista_get_option( 'hamista_licenses', 'count_local_domains', false );

		if ( ! ( $is_local && ! $count_local ) ) {
			$limit = (int) $license['activation_limit'];
			$count = $this->activations->count_counted( $license['id'] );
			if ( $limit > 0 && $count >= $limit ) {
				return Result::error( 'activation_limit_reached', __( 'The activation limit for this license has been reached.', 'hamista-license-manager' ), 403, [ 'license' => $this->payload( $license ) ] );
			}
		}

		$this->activations->add(
			[
				'license_id'     => $license['id'],
				'domain'         => $normalized,
				'instance_id'    => (string) ( $ctx['instance'] ?? '' ),
				'is_local'       => $is_local,
				'ip_address'     => (string) ( $ctx['ip'] ?? '' ),
				'client_version' => (string) ( $ctx['version'] ?? '' ),
			]
		);

		$license = $this->licenses->find( $license['id'] ) ?? $license;
		return Result::ok( 'activated', __( 'The license was activated.', 'hamista-license-manager' ), [ 'license' => $this->payload( $license ) ] );
	}

	/**
	 * Deactivates a license for a domain.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain URL, host or IP.
	 * @return Result
	 */
	public function deactivate( string $key, string $domain ): Result {
		$license = $this->licenses->find_by_key( $key );
		if ( null === $license ) {
			return Result::error( 'license_not_found', __( 'This license key was not found.', 'hamista-license-manager' ), 404 );
		}

		$normalized = Domain::normalize( $domain );
		if ( '' === $normalized ) {
			return Result::error( 'invalid_domain', __( 'The domain is not valid.', 'hamista-license-manager' ), 400 );
		}

		if ( ! $this->activations->remove( $license['id'], $normalized ) ) {
			return Result::error( 'not_activated', __( 'This domain is not activated for this license.', 'hamista-license-manager' ), 404 );
		}

		$license = $this->licenses->find( $license['id'] ) ?? $license;
		return Result::ok( 'deactivated', __( 'The domain was deactivated.', 'hamista-license-manager' ), [ 'license' => $this->payload( $license ) ] );
	}

	/**
	 * Checks a license's status for a domain, without activating it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain URL, host or IP.
	 * @param array  $ctx    Unused for now; kept for parity with activate()/the future REST layer.
	 * @return Result
	 */
	public function check( string $key, string $domain, array $ctx = [] ): Result { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- forward-compatible signature (spec §7 REST /check).
		$license = $this->licenses->find_by_key( $key );
		if ( null === $license ) {
			return Result::error( 'license_not_found', __( 'This license key was not found.', 'hamista-license-manager' ), 404 );
		}

		$status_error = $this->check_status_and_expiry( $license );
		if ( null !== $status_error ) {
			return $status_error;
		}
		$license = $this->licenses->find( $license['id'] ) ?? $license;

		$normalized = Domain::normalize( $domain );
		if ( '' === $normalized ) {
			return Result::error( 'invalid_domain', __( 'The domain is not valid.', 'hamista-license-manager' ), 400 );
		}

		$activation = $this->activations->find( $license['id'], $normalized );
		if ( null !== $activation ) {
			$this->activations->touch( $activation['id'] );
		}

		return Result::ok(
			'valid',
			__( 'The license is valid.', 'hamista-license-manager' ),
			[
				'license'   => $this->payload( $license ),
				'activated' => null !== $activation,
			]
		);
	}

	/**
	 * Issues (or, if already issued, returns) the licenses for an order item.
	 *
	 * Idempotent through the order item meta `_hamista_license_ids`.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order              $order Order.
	 * @param \WC_Order_Item_Product $item  Line item.
	 * @return int[] The license ids.
	 */
	public function issue_for_order_item( \WC_Order $order, \WC_Order_Item_Product $item ): array {
		$existing = $item->get_meta( '_hamista_license_ids', true );
		if ( is_array( $existing ) && [] !== $existing ) {
			return array_map( 'intval', $existing );
		}

		$product = $item->get_product();
		if ( ! $product instanceof \WC_Product ) {
			return [];
		}

		$settings   = $this->resolve_product_license_settings( $product );
		$qty        = max( 1, (int) $item->get_quantity() );
		$user_id    = $order->get_customer_id();
		$expires_at = 0 === $settings['validity_days']
			? null
			: gmdate( 'Y-m-d H:i:s', strtotime( '+' . $settings['validity_days'] . ' days', current_time( 'timestamp', true ) ) );

		$prefix = (string) hamista_get_option( 'hamista_licenses', 'key_prefix', 'HMST' );
		$ids    = [];

		for ( $i = 0; $i < $qty; $i++ ) {
			$key = $this->generate_unique_key( $prefix );
			$id  = $this->licenses->create(
				[
					'license_key'      => $key,
					'product_id'       => $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(),
					'variation_id'     => $product->get_parent_id() ? $product->get_id() : 0,
					'order_id'         => $order->get_id(),
					'order_item_id'    => $item->get_id(),
					'user_id'          => $user_id,
					'status'           => 'active',
					'activation_limit' => $settings['activation_limit'],
					'expires_at'       => $expires_at,
				]
			);
			if ( $id > 0 ) {
				$ids[] = $id;
				/**
				 * Fires once per license issued for an order.
				 *
				 * @since 1.0.0
				 *
				 * @param int $license_id License id.
				 * @param int $order_id   Order id.
				 */
				do_action( 'hamista_license_issued', $id, $order->get_id() );
			}
		}

		if ( [] === $ids ) {
			return [];
		}

		$item->update_meta_data( '_hamista_license_ids', $ids );
		$item->save();

		$keys = array_map(
			fn( int $id ) => ( $this->licenses->find( $id )['license_key'] ?? '' ),
			$ids
		);
		$order->add_order_note(
			sprintf(
				/* translators: 1: number of licenses, 2: comma-separated license keys. */
				_n( 'Issued %1$d license: %2$s', 'Issued %1$d licenses: %2$s', count( $ids ), 'hamista-license-manager' ),
				count( $ids ),
				implode( ', ', array_filter( $keys ) )
			)
		);

		return $ids;
	}

	/**
	 * Revokes every license issued for an order.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $reason Shown in the order note.
	 * @return Result
	 */
	public function revoke_for_order( \WC_Order $order, string $reason ): Result {
		$revoked = [];
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$ids = $item->get_meta( '_hamista_license_ids', true );
			foreach ( is_array( $ids ) ? $ids : [] as $id ) {
				$result = $this->set_status( (int) $id, 'revoked' );
				if ( $result->success ) {
					$revoked[] = (int) $id;
				}
			}
		}

		if ( [] !== $revoked ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: number of licenses, 2: reason. */
					__( 'Revoked %1$d license(s): %2$s', 'hamista-license-manager' ),
					count( $revoked ),
					$reason
				)
			);
		}

		return Result::ok( 'revoked', __( 'Licenses revoked.', 'hamista-license-manager' ), [ 'license_ids' => $revoked ] );
	}

	/**
	 * Extends a license's expiry and reactivates it if it had expired.
	 *
	 * The new expiry is `days` after max(now, current expires_at); a
	 * lifetime license (expires_at null) is extended from now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $license_id License id.
	 * @param int $days       Days to add.
	 * @return Result
	 */
	public function renew( int $license_id, int $days ): Result {
		$license = $this->licenses->find( $license_id );
		if ( null === $license ) {
			return Result::error( 'license_not_found', __( 'This license was not found.', 'hamista-license-manager' ), 404 );
		}

		$now  = current_time( 'timestamp', true );
		$base = null !== $license['expires_at'] ? max( $now, strtotime( $license['expires_at'] . ' UTC' ) ) : $now;
		$new_expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+' . max( 0, $days ) . ' days', $base ) );

		$data = [ 'expires_at' => $new_expiry ];
		if ( 'expired' === $license['status'] ) {
			$data['status'] = 'active';
		}
		$this->licenses->update( $license_id, $data );

		if ( isset( $data['status'] ) ) {
			/** This action is documented in class-license-service.php (set_status()). */
			do_action( 'hamista_license_status_changed', $license_id, $license['status'], $data['status'] );
		}

		$license = $this->licenses->find( $license_id );
		return Result::ok( 'renewed', __( 'The license was renewed.', 'hamista-license-manager' ), [ 'license' => $this->payload( $license ) ] );
	}

	/**
	 * Sets a license's status.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $id     License id.
	 * @param string $status One of STATUSES.
	 * @return Result
	 */
	public function set_status( int $id, string $status ): Result {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return Result::error( 'invalid_request', __( 'Invalid license status.', 'hamista-license-manager' ), 400 );
		}

		$license = $this->licenses->find( $id );
		if ( null === $license ) {
			return Result::error( 'license_not_found', __( 'This license was not found.', 'hamista-license-manager' ), 404 );
		}

		$old = $license['status'];
		if ( $old !== $status ) {
			$this->licenses->update( $id, [ 'status' => $status ] );
			/**
			 * Fires when a license's status changes.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $license_id License id.
			 * @param string $old_status Previous status.
			 * @param string $new_status New status.
			 */
			do_action( 'hamista_license_status_changed', $id, $old, $status );
		}

		$license = $this->licenses->find( $id );
		return Result::ok( 'status_changed', __( 'The license status was updated.', 'hamista-license-manager' ), [ 'license' => $this->payload( $license ) ] );
	}

	/**
	 * Checks a license's status and expiry, expiring it in the DB when its
	 * `expires_at` has passed.
	 *
	 * @param array $license License row.
	 * @return Result|null An error Result to return immediately, or null to continue.
	 */
	private function check_status_and_expiry( array $license ): ?Result {
		if ( 'revoked' === $license['status'] ) {
			return Result::error( 'license_revoked', __( 'This license has been revoked.', 'hamista-license-manager' ), 403, [ 'license' => $this->payload( $license ) ] );
		}
		if ( 'inactive' === $license['status'] ) {
			return Result::error( 'license_inactive', __( 'This license is inactive.', 'hamista-license-manager' ), 403, [ 'license' => $this->payload( $license ) ] );
		}

		if ( null !== $license['expires_at'] && strtotime( $license['expires_at'] . ' UTC' ) < current_time( 'timestamp', true ) && 'expired' !== $license['status'] ) {
			$this->set_status( $license['id'], 'expired' );
		}

		if ( null !== $license['expires_at'] && strtotime( $license['expires_at'] . ' UTC' ) < current_time( 'timestamp', true ) ) {
			$license = $this->licenses->find( $license['id'] ) ?? $license;
			return Result::error( 'license_expired', __( 'This license has expired.', 'hamista-license-manager' ), 403, [ 'license' => $this->payload( $license ) ] );
		}

		return null;
	}

	/**
	 * Whether the ctx-provided product identifier mismatches the license's product.
	 *
	 * @param array $license License row.
	 * @param array $ctx     `product_id` and/or `slug`.
	 * @return bool True on a mismatch.
	 */
	private function product_mismatch( array $license, array $ctx ): bool {
		$product_id = $this->resolve_product_id( $ctx );
		if ( null === $product_id ) {
			return false;
		}
		return $product_id !== (int) $license['product_id'];
	}

	/**
	 * Resolves a ctx `product_id` or `slug` to a product id.
	 *
	 * @param array $ctx `product_id` and/or `slug`.
	 * @return int|null Null when neither is given (no check requested).
	 */
	private function resolve_product_id( array $ctx ): ?int {
		if ( ! empty( $ctx['product_id'] ) ) {
			return (int) $ctx['product_id'];
		}
		if ( ! empty( $ctx['slug'] ) && function_exists( 'wc_get_products' ) ) {
			$ids = wc_get_products(
				[
					'meta_key'   => '_hamista_lm_slug', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => sanitize_title( (string) $ctx['slug'] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'limit'      => 1,
					'return'     => 'ids',
					'status'     => 'any',
				]
			);
			return [] === $ids ? null : (int) $ids[0];
		}
		return null;
	}

	/**
	 * Resolves the license settings for a product (or variation): the
	 * variation's own values override the parent product's, which fall back
	 * to the plugin defaults.
	 *
	 * @param \WC_Product $product Product or variation.
	 * @return array{activation_limit:int,validity_days:int}
	 */
	private function resolve_product_license_settings( \WC_Product $product ): array {
		$default_limit    = (int) hamista_get_option( 'hamista_licenses', 'default_activation_limit', 1 );
		$default_validity = (int) hamista_get_option( 'hamista_licenses', 'default_validity_days', 365 );

		$parent_id = $product->get_parent_id();
		$parent    = $parent_id ? wc_get_product( $parent_id ) : $product;

		$limit    = self::meta_or( $product, '_hamista_lm_activation_limit' );
		$validity = self::meta_or( $product, '_hamista_lm_validity_days' );

		if ( null === $limit && $parent instanceof \WC_Product ) {
			$limit = self::meta_or( $parent, '_hamista_lm_activation_limit' );
		}
		if ( null === $validity && $parent instanceof \WC_Product ) {
			$validity = self::meta_or( $parent, '_hamista_lm_validity_days' );
		}

		return [
			'activation_limit' => null === $limit ? $default_limit : (int) $limit,
			'validity_days'    => null === $validity ? $default_validity : (int) $validity,
		];
	}

	/**
	 * Reads a numeric meta value, or null when it is empty/unset (so callers
	 * can fall through to the next level of the override chain).
	 *
	 * @param \WC_Product $product Product or variation.
	 * @param string      $key     Meta key.
	 * @return int|null
	 */
	private static function meta_or( \WC_Product $product, string $key ): ?int {
		$value = $product->get_meta( $key, true );
		return '' === $value || null === $value ? null : (int) $value;
	}

	/**
	 * Generates a license key that does not collide with an existing one.
	 *
	 * @param string $prefix Key prefix.
	 * @return string
	 */
	private function generate_unique_key( string $prefix ): string {
		do {
			$key = Key_Generator::generate( $prefix );
		} while ( null !== $this->licenses->find_by_key( $key ) );
		return $key;
	}

	/**
	 * Local-domain fnmatch patterns from the `local_patterns` setting
	 * (newline- or comma-separated).
	 *
	 * @return string[]
	 */
	private function local_patterns(): array {
		$raw = (string) hamista_get_option( 'hamista_licenses', 'local_patterns', '' );
		if ( '' === trim( $raw ) ) {
			return [];
		}
		$patterns = preg_split( '/[\r\n,]+/', $raw );
		return array_values( array_filter( array_map( 'trim', is_array( $patterns ) ? $patterns : [] ) ) );
	}

	/**
	 * Builds the `license` payload for a Result (spec §7: status, expires_at,
	 * activation_limit, activation_count, product_id).
	 *
	 * @param array $license License row.
	 * @return array
	 */
	private function payload( array $license ): array {
		return [
			'status'            => $license['status'],
			'expires_at'        => null === $license['expires_at'] ? null : gmdate( 'c', strtotime( $license['expires_at'] . ' UTC' ) ),
			'activation_limit'  => (int) $license['activation_limit'],
			'activation_count'  => (int) $license['activation_count'],
			'product_id'        => (int) $license['product_id'],
		];
	}
}

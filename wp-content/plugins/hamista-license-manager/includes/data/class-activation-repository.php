<?php
/**
 * Data access for the `hamista_license_activations` table (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Data;

defined( 'ABSPATH' ) || exit;

/**
 * `$wpdb`-only repository for license activations.
 *
 * `licenses.activation_count` (the total number of currently activated
 * domains, local and non-local) is kept in sync after every add() and
 * remove(). It is a display counter; the activation-limit check uses
 * count_counted(), which can exclude local domains per settings.
 *
 * @since 1.0.0
 */
final class Activation_Repository {

	/**
	 * The activations table name, with the site's prefix.
	 *
	 * @return string
	 */
	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hamista_license_activations';
	}

	/**
	 * The licenses table name, with the site's prefix.
	 *
	 * @return string
	 */
	private function licenses_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hamista_licenses';
	}

	/**
	 * Finds an activation by license and (already normalised) domain.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $license_id License id.
	 * @param string $domain     Normalised domain.
	 * @return array|null
	 */
	public function find( int $license_id, string $domain ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE license_id = %d AND domain = %s", $license_id, $domain ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return is_array( $row ) ? self::cast( $row ) : null;
	}

	/**
	 * All activations of a license, most recent first.
	 *
	 * @since 1.0.0
	 *
	 * @param int $license_id License id.
	 * @return array<int, array>
	 */
	public function for_license( int $license_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE license_id = %d ORDER BY activated_at DESC", $license_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return array_map( [ self::class, 'cast' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Adds an activation and syncs `licenses.activation_count`.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data {
	 *     @type int    $license_id     Required.
	 *     @type string $domain         Required; already normalised.
	 *     @type string $instance_id
	 *     @type bool   $is_local
	 *     @type string $ip_address
	 *     @type string $client_version
	 * }
	 * @return int The new activation id, or 0 on failure.
	 */
	public function add( array $data ): int {
		global $wpdb;
		$now = current_time( 'mysql', true );

		$inserted = $wpdb->insert(
			$this->table(),
			[
				'license_id'      => (int) ( $data['license_id'] ?? 0 ),
				'domain'          => (string) ( $data['domain'] ?? '' ),
				'instance_id'     => (string) ( $data['instance_id'] ?? '' ),
				'is_local'        => ! empty( $data['is_local'] ) ? 1 : 0,
				'ip_address'      => (string) ( $data['ip_address'] ?? '' ),
				'client_version'  => (string) ( $data['client_version'] ?? '' ),
				'activated_at'    => $now,
				'last_checked_at' => $now,
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
		);
		if ( false === $inserted ) {
			return 0;
		}

		$this->sync_activation_count( (int) ( $data['license_id'] ?? 0 ) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Removes an activation and syncs `licenses.activation_count`.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $license_id License id.
	 * @param string $domain     Normalised domain.
	 * @return bool True when a row was deleted.
	 */
	public function remove( int $license_id, string $domain ): bool {
		global $wpdb;
		$deleted = $wpdb->delete(
			$this->table(),
			[
				'license_id' => $license_id,
				'domain'     => $domain,
			],
			[ '%d', '%s' ]
		);

		if ( $deleted ) {
			$this->sync_activation_count( $license_id );
		}
		return (bool) $deleted;
	}

	/**
	 * Updates `last_checked_at` to now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Activation id.
	 * @return bool
	 */
	public function touch( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			$this->table(),
			[ 'last_checked_at' => current_time( 'mysql', true ) ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Counts activations that count against the activation limit: every
	 * activation, or only non-local ones when `count_local_domains` is off.
	 *
	 * @since 1.0.0
	 *
	 * @param int $license_id License id.
	 * @return int
	 */
	public function count_counted( int $license_id ): int {
		global $wpdb;
		$count_local = (bool) hamista_get_option( 'hamista_licenses', 'count_local_domains', false );

		$sql = $count_local
			? "SELECT COUNT(*) FROM {$this->table()} WHERE license_id = %d"
			: "SELECT COUNT(*) FROM {$this->table()} WHERE license_id = %d AND is_local = 0";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $license_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Recomputes and stores the total activation count on the license row.
	 *
	 * @param int $license_id License id.
	 */
	private function sync_activation_count( int $license_id ): void {
		global $wpdb;
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE license_id = %d", $license_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->update( $this->licenses_table(), [ 'activation_count' => $total ], [ 'id' => $license_id ], [ '%d' ], [ '%d' ] );
	}

	/**
	 * Casts a raw DB row into a typed value array.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	private static function cast( array $row ): array {
		return [
			'id'              => (int) $row['id'],
			'license_id'      => (int) $row['license_id'],
			'domain'          => (string) $row['domain'],
			'instance_id'     => (string) $row['instance_id'],
			'is_local'        => (bool) $row['is_local'],
			'ip_address'      => (string) $row['ip_address'],
			'client_version'  => (string) $row['client_version'],
			'activated_at'    => (string) $row['activated_at'],
			'last_checked_at' => empty( $row['last_checked_at'] ) ? null : (string) $row['last_checked_at'],
		];
	}
}

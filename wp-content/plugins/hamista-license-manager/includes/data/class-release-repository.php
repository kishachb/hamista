<?php
/**
 * Data access for the `hamista_releases` table (spec §7).
 *
 * Schema-only for LM1: publishing, uploads and the update API arrive in LM2.
 *
 * @package Hamista\License
 */

namespace Hamista\License\Data;

use Hamista\License\Support\Version;

defined( 'ABSPATH' ) || exit;

/**
 * `$wpdb`-only repository for releases.
 *
 * @since 1.0.0
 */
final class Release_Repository {

	/**
	 * The table name, with the site's prefix.
	 *
	 * @return string
	 */
	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hamista_releases';
	}

	/**
	 * The latest published release of a product on a channel.
	 *
	 * Published rows for the product/channel are fetched and compared in
	 * PHP with Version::compare(), since version strings don't sort
	 * correctly as plain SQL text (e.g. '10.0.0' < '9.0.0' alphabetically).
	 *
	 * @since 1.0.0
	 *
	 * @param int    $product_id Product id.
	 * @param string $channel    'stable' (default) or 'beta'.
	 * @return array|null
	 */
	public function latest( int $product_id, string $channel = 'stable' ): ?array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE product_id = %d AND channel = %s AND status = 'published'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$product_id,
				$channel
			),
			ARRAY_A
		);
		$rows = array_map( [ self::class, 'cast' ], is_array( $rows ) ? $rows : [] );
		if ( [] === $rows ) {
			return null;
		}

		usort(
			$rows,
			static function ( array $a, array $b ): int {
				if ( ! Version::is_valid( $a['version'] ) || ! Version::is_valid( $b['version'] ) ) {
					return strcmp( $a['version'], $b['version'] );
				}
				return Version::compare( $a['version'], $b['version'] );
			}
		);

		return end( $rows );
	}

	/**
	 * Finds a release by id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Release id.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $row ) ? self::cast( $row ) : null;
	}

	/**
	 * All releases of a product, newest first.
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id Product id.
	 * @return array<int, array>
	 */
	public function for_product( int $product_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE product_id = %d ORDER BY id DESC", $product_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return array_map( [ self::class, 'cast' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Creates a release row. LM2 wires this to an upload/publish flow.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Column values; see the table's columns in spec §7.
	 * @return int The new release id, or 0 on failure.
	 */
	public function create( array $data ): int {
		global $wpdb;

		$row = [
			'product_id'     => (int) ( $data['product_id'] ?? 0 ),
			'version'        => (string) ( $data['version'] ?? '' ),
			'channel'        => (string) ( $data['channel'] ?? 'stable' ),
			'status'         => (string) ( $data['status'] ?? 'draft' ),
			'changelog'      => (string) ( $data['changelog'] ?? '' ),
			'file_name'      => (string) ( $data['file_name'] ?? '' ),
			'file_size'      => (int) ( $data['file_size'] ?? 0 ),
			'file_hash'      => (string) ( $data['file_hash'] ?? '' ),
			'requires_wp'    => (string) ( $data['requires_wp'] ?? '' ),
			'tested_wp'      => (string) ( $data['tested_wp'] ?? '' ),
			'requires_php'   => (string) ( $data['requires_php'] ?? '' ),
			'download_count' => 0,
			'released_at'    => $data['released_at'] ?? null,
			'created_at'     => current_time( 'mysql', true ),
		];

		$inserted = $wpdb->insert( $this->table(), $row, [ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ] );
		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Casts a raw DB row into a typed value array.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	private static function cast( array $row ): array {
		return [
			'id'             => (int) $row['id'],
			'product_id'     => (int) $row['product_id'],
			'version'        => (string) $row['version'],
			'channel'        => (string) $row['channel'],
			'status'         => (string) $row['status'],
			'changelog'      => (string) ( $row['changelog'] ?? '' ),
			'file_name'      => (string) $row['file_name'],
			'file_size'      => (int) $row['file_size'],
			'file_hash'      => (string) $row['file_hash'],
			'requires_wp'    => (string) $row['requires_wp'],
			'tested_wp'      => (string) $row['tested_wp'],
			'requires_php'   => (string) $row['requires_php'],
			'download_count' => (int) $row['download_count'],
			'released_at'    => empty( $row['released_at'] ) ? null : (string) $row['released_at'],
			'created_at'     => (string) $row['created_at'],
		];
	}
}

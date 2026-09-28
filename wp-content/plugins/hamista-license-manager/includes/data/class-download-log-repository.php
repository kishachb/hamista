<?php
/**
 * Data access for the `hamista_download_log` table (spec §7).
 *
 * Schema-only for LM1: the download service arrives in LM2.
 *
 * @package Hamista\License
 */

namespace Hamista\License\Data;

defined( 'ABSPATH' ) || exit;

/**
 * `$wpdb`-only repository for the download log.
 *
 * @since 1.0.0
 */
final class Download_Log_Repository {

	/**
	 * The table name, with the site's prefix.
	 *
	 * @return string
	 */
	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hamista_download_log';
	}

	/**
	 * Logs a download.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data {
	 *     @type int    $release_id
	 *     @type int    $product_id
	 *     @type int    $license_id
	 *     @type int    $user_id
	 *     @type string $source     'dashboard' or 'api'.
	 *     @type string $ip_address
	 * }
	 * @return int The new row id, or 0 on failure.
	 */
	public function log( array $data ): int {
		global $wpdb;
		$inserted = $wpdb->insert(
			$this->table(),
			[
				'release_id' => (int) ( $data['release_id'] ?? 0 ),
				'product_id' => (int) ( $data['product_id'] ?? 0 ),
				'license_id' => (int) ( $data['license_id'] ?? 0 ),
				'user_id'    => (int) ( $data['user_id'] ?? 0 ),
				'source'     => (string) ( $data['source'] ?? 'dashboard' ),
				'ip_address' => (string) ( $data['ip_address'] ?? '' ),
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%d', '%d', '%s', '%s', '%s' ]
		);
		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * The most recent download of a product by a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id    User id.
	 * @param int $product_id Product id.
	 * @return array|null
	 */
	public function last_download( int $user_id, int $product_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d AND product_id = %d ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$product_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? self::cast( $row ) : null;
	}

	/**
	 * Casts a raw DB row into a typed value array.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	private static function cast( array $row ): array {
		return [
			'id'         => (int) $row['id'],
			'release_id' => (int) $row['release_id'],
			'product_id' => (int) $row['product_id'],
			'license_id' => (int) $row['license_id'],
			'user_id'    => (int) $row['user_id'],
			'source'     => (string) $row['source'],
			'ip_address' => (string) $row['ip_address'],
			'created_at' => (string) $row['created_at'],
		];
	}
}

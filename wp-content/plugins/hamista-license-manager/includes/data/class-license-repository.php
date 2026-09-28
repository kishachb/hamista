<?php
/**
 * Data access for the `hamista_licenses` table (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Data;

use Hamista\License\Support\Key_Generator;

defined( 'ABSPATH' ) || exit;

/**
 * `$wpdb`-only repository for licenses. Every query is prepared. Returned
 * rows are value arrays with typed casting; license keys are stored
 * upper-case and normalised (Key_Generator::normalize()).
 *
 * @since 1.0.0
 */
final class License_Repository {

	/**
	 * Columns that may be sorted on (query()'s `orderby` whitelist).
	 */
	private const SORTABLE_COLUMNS = [ 'id', 'license_key', 'status', 'created_at', 'updated_at', 'expires_at', 'activation_count' ];

	/**
	 * The table name, with the site's prefix.
	 *
	 * @return string
	 */
	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hamista_licenses';
	}

	/**
	 * Finds a license by id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id License id.
	 * @return array|null Value array, or null when not found.
	 */
	public function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name, not a value.
		return is_array( $row ) ? self::cast( $row ) : null;
	}

	/**
	 * Finds a license by key. The key is normalised before the lookup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key, in any casing/spacing Key_Generator::normalize() accepts.
	 * @return array|null
	 */
	public function find_by_key( string $key ): ?array {
		global $wpdb;
		$normalized = Key_Generator::normalize( $key );
		$row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE license_key = %s", $normalized ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $row ) ? self::cast( $row ) : null;
	}

	/**
	 * Finds a license by the WooCommerce order item that issued it.
	 *
	 * Kept as a convenience for a single-license item; issue_for_order_item()
	 * itself reads the item meta directly, since one item can hold several ids.
	 *
	 * @since 1.0.0
	 *
	 * @param int $item_id Order item id.
	 * @return array|null
	 */
	public function find_by_order_item( int $item_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE order_item_id = %d ORDER BY id ASC LIMIT 1", $item_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $row ) ? self::cast( $row ) : null;
	}

	/**
	 * Licenses owned by a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id User id.
	 * @param array $args    `status` (string|string[]), `orderby`, `order`.
	 * @return array<int, array>
	 */
	public function for_user( int $user_id, array $args = [] ): array {
		global $wpdb;

		$where  = [ 'user_id = %d' ];
		$values = [ $user_id ];

		if ( ! empty( $args['status'] ) ) {
			[ $clause, $status_values ] = self::in_clause( 'status', (array) $args['status'] );
			$where[]                    = $clause;
			array_push( $values, ...$status_values );
		}

		$orderby = self::sanitize_orderby( $args['orderby'] ?? 'created_at' );
		$order   = self::sanitize_order( $args['order'] ?? 'DESC' );

		$sql = "SELECT * FROM {$this->table()} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order}";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return array_map( [ self::class, 'cast' ], is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Searches and filters licenses with pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     @type string       $search   Matches the license key (normalised, partial) or a
	 *                                  numeric order id (exact) or the owner's email (partial).
	 *     @type string|array $status   Status filter.
	 *     @type int          $product_id Product filter.
	 *     @type string       $orderby  One of SORTABLE_COLUMNS. Default 'created_at'.
	 *     @type string       $order    'ASC' or 'DESC'. Default 'DESC'.
	 *     @type int          $per_page Default 20. 0 = no limit.
	 *     @type int          $page     1-based. Default 1.
	 * }
	 * @return array{items: array<int, array>, total: int}
	 */
	public function query( array $args = [] ): array {
		global $wpdb;
		$table = $this->table();

		$where  = [ '1=1' ];
		$values = [];
		$joins  = '';

		if ( ! empty( $args['search'] ) ) {
			$search = trim( (string) $args['search'] );
			$ors    = [];

			$normalized = Key_Generator::normalize( $search );
			if ( '' !== $normalized ) {
				$ors[]    = "{$table}.license_key LIKE %s";
				$values[] = '%' . $wpdb->esc_like( $normalized ) . '%';
			}
			if ( ctype_digit( $search ) ) {
				$ors[]    = "{$table}.order_id = %d";
				$values[] = (int) $search;
			}
			if ( str_contains( $search, '@' ) || ! ctype_digit( $search ) ) {
				$joins    = " LEFT JOIN {$wpdb->users} u ON u.ID = {$table}.user_id";
				$ors[]    = 'u.user_email LIKE %s';
				$values[] = '%' . $wpdb->esc_like( $search ) . '%';
			}

			if ( [] !== $ors ) {
				$where[] = '(' . implode( ' OR ', $ors ) . ')';
			}
		}

		if ( ! empty( $args['status'] ) ) {
			[ $clause, $status_values ] = self::in_clause( 'status', (array) $args['status'], $table );
			$where[]                    = $clause;
			array_push( $values, ...$status_values );
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where[]  = "{$table}.product_id = %d";
			$values[] = (int) $args['product_id'];
		}

		$orderby = self::sanitize_orderby( $args['orderby'] ?? 'created_at', $table );
		$order   = self::sanitize_order( $args['order'] ?? 'DESC' );
		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table}{$joins} WHERE {$where_sql}";
		$total     = [] === $values
			? (int) $wpdb->get_var( $count_sql ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- no user values.
			: (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		$per_page = array_key_exists( 'per_page', $args ) ? max( 0, (int) $args['per_page'] ) : 20;
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$limit_sql = '';
		if ( $per_page > 0 ) {
			$limit_sql = ' LIMIT %d OFFSET %d';
			$values[]  = $per_page;
			$values[]  = ( $page - 1 ) * $per_page;
		}

		$select_sql = "SELECT {$table}.* FROM {$table}{$joins} WHERE {$where_sql} ORDER BY {$orderby} {$order}{$limit_sql}";
		$rows       = [] === $values
			? $wpdb->get_results( $select_sql, ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- no user values.
			: $wpdb->get_results( $wpdb->prepare( $select_sql, $values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return [
			'items' => array_map( [ self::class, 'cast' ], is_array( $rows ) ? $rows : [] ),
			'total' => $total,
		];
	}

	/**
	 * Creates a license.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data {
	 *     @type string      $license_key      Required; normalised and upper-cased.
	 *     @type int         $product_id
	 *     @type int         $variation_id
	 *     @type int         $order_id
	 *     @type int         $order_item_id
	 *     @type int         $user_id
	 *     @type string      $status           Default 'active'.
	 *     @type int         $activation_limit Default 0 (unlimited).
	 *     @type string|null $expires_at       'Y-m-d H:i:s' (UTC) or null for lifetime.
	 *     @type string      $notes
	 * }
	 * @return int The new license id, or 0 on failure.
	 */
	public function create( array $data ): int {
		global $wpdb;
		$now = current_time( 'mysql', true );

		$row = [
			'license_key'      => Key_Generator::normalize( (string) ( $data['license_key'] ?? '' ) ),
			'product_id'       => (int) ( $data['product_id'] ?? 0 ),
			'variation_id'     => (int) ( $data['variation_id'] ?? 0 ),
			'order_id'         => (int) ( $data['order_id'] ?? 0 ),
			'order_item_id'    => (int) ( $data['order_item_id'] ?? 0 ),
			'user_id'          => (int) ( $data['user_id'] ?? 0 ),
			'status'           => (string) ( $data['status'] ?? 'active' ),
			'activation_limit' => (int) ( $data['activation_limit'] ?? 0 ),
			'activation_count' => (int) ( $data['activation_count'] ?? 0 ),
			'expires_at'       => array_key_exists( 'expires_at', $data ) ? $data['expires_at'] : null,
			'created_at'       => $now,
			'updated_at'       => $now,
			'notes'            => (string) ( $data['notes'] ?? '' ),
		];

		$formats = [ '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ];
		$inserted = $wpdb->insert( $this->table(), $row, $formats );
		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Updates a license.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id   License id.
	 * @param array $data Columns to update (any subset of create()'s keys).
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$allowed = [
			'license_key'      => '%s',
			'product_id'       => '%d',
			'variation_id'     => '%d',
			'order_id'         => '%d',
			'order_item_id'    => '%d',
			'user_id'          => '%d',
			'status'           => '%s',
			'activation_limit' => '%d',
			'activation_count' => '%d',
			'expires_at'       => '%s',
			'notes'            => '%s',
		];

		$row     = [];
		$formats = [];
		foreach ( $allowed as $column => $format ) {
			if ( ! array_key_exists( $column, $data ) ) {
				continue;
			}
			$row[ $column ] = 'license_key' === $column ? Key_Generator::normalize( (string) $data[ $column ] ) : $data[ $column ];
			$formats[]      = $format;
		}
		if ( [] === $row ) {
			return true;
		}

		$row['updated_at'] = current_time( 'mysql', true );
		$formats[]          = '%s';

		return false !== $wpdb->update( $this->table(), $row, [ 'id' => $id ], $formats, [ '%d' ] );
	}

	/**
	 * Deletes a license.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id License id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( $this->table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Counts licenses grouped by status.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int> status => count. Missing statuses are simply absent.
	 */
	public function count_by_status(): array {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$this->table()} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- no user values.

		$counts = [];
		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$counts[ (string) $row['status'] ] = (int) $row['total'];
		}
		return $counts;
	}

	/**
	 * Casts a raw DB row into a typed value array.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	private static function cast( array $row ): array {
		return [
			'id'               => (int) $row['id'],
			'license_key'      => (string) $row['license_key'],
			'product_id'       => (int) $row['product_id'],
			'variation_id'     => (int) $row['variation_id'],
			'order_id'         => (int) $row['order_id'],
			'order_item_id'    => (int) $row['order_item_id'],
			'user_id'          => (int) $row['user_id'],
			'status'           => (string) $row['status'],
			'activation_limit' => (int) $row['activation_limit'],
			'activation_count' => (int) $row['activation_count'],
			'expires_at'       => empty( $row['expires_at'] ) ? null : (string) $row['expires_at'],
			'created_at'       => (string) $row['created_at'],
			'updated_at'       => (string) $row['updated_at'],
			'notes'            => (string) ( $row['notes'] ?? '' ),
		];
	}

	/**
	 * Builds a prepared `column IN (...)` clause.
	 *
	 * @param string $column Column name.
	 * @param array  $values Values.
	 * @param string $table  Optional table prefix for the column.
	 * @return array{0:string,1:array}
	 */
	private static function in_clause( string $column, array $values, string $table = '' ): array {
		$values      = array_values( array_map( 'strval', $values ) );
		$placeholders = implode( ', ', array_fill( 0, count( $values ), '%s' ) );
		$prefix      = '' === $table ? '' : $table . '.';
		return [ "{$prefix}{$column} IN ({$placeholders})", $values ];
	}

	/**
	 * Whitelists an `orderby` value.
	 *
	 * @param mixed  $orderby Requested column.
	 * @param string $table   Optional table prefix.
	 * @return string
	 */
	private static function sanitize_orderby( $orderby, string $table = '' ): string {
		$orderby = is_string( $orderby ) ? $orderby : 'created_at';
		if ( ! in_array( $orderby, self::SORTABLE_COLUMNS, true ) ) {
			$orderby = 'created_at';
		}
		return ( '' === $table ? '' : $table . '.' ) . $orderby;
	}

	/**
	 * Whitelists an `order` value.
	 *
	 * @param mixed $order Requested direction.
	 * @return string 'ASC' or 'DESC'.
	 */
	private static function sanitize_order( $order ): string {
		return 'ASC' === strtoupper( (string) $order ) ? 'ASC' : 'DESC';
	}
}

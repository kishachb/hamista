<?php
/**
 * Prepared queries on `hamista_notifications`.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Every notification query the plugin needs, all through `$wpdb->prepare()`.
 *
 * @since 1.0.0
 */
final class Notification_Repository {

	/**
	 * Table name, with the site prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hamista_notifications';
	}

	/**
	 * Inserts a notification row.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data `user_id`, `type`, `title`, `message`, `link` (already sanitised by the caller).
	 * @return int Insert id, or 0 on failure.
	 */
	public function create( array $data ): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table(),
			[
				'user_id'    => (int) ( $data['user_id'] ?? 0 ),
				'type'       => (string) ( $data['type'] ?? 'info' ),
				'title'      => (string) ( $data['title'] ?? '' ),
				'message'    => (string) ( $data['message'] ?? '' ),
				'link'       => (string) ( $data['link'] ?? '' ),
				'is_read'    => 0,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);

		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * One page of a user's notifications, newest first.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id  User id.
	 * @param int $page     1-based page number.
	 * @param int $per_page Rows per page.
	 * @return array{items:array<int,array<string,mixed>>,total:int,pages:int}
	 */
	public function for_user( int $user_id, int $page = 1, int $per_page = 20 ): array {
		global $wpdb;

		$page     = max( 1, $page );
		$per_page = max( 1, $per_page );
		$offset   = ( $page - 1 ) * $per_page;
		$table    = $this->table();

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name only.
				$user_id,
				$per_page,
				$offset
			),
			ARRAY_A
		);
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name only.
				$user_id
			)
		);

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
			'pages' => (int) max( 1, ceil( $total / $per_page ) ),
		];
	}

	/**
	 * Unread count for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return int
	 */
	public function count_unread( int $user_id ): int {
		global $wpdb;
		$table = $this->table();
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name only.
				$user_id
			)
		);
	}

	/**
	 * A single notification row.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Notification id.
	 * @return array<string, mixed>|null
	 */
	public function get( int $id ): ?array {
		global $wpdb;
		$table = $this->table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name only.
				$id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Marks one notification read, only if it belongs to `$user_id` (IDOR-safe:
	 * the ownership check is in the WHERE clause, not a separate lookup).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id      Notification id.
	 * @param int $user_id Owner user id.
	 * @return bool Whether a row was updated.
	 */
	public function mark_read( int $id, int $user_id ): bool {
		global $wpdb;
		$updated = $wpdb->update(
			$this->table(),
			[ 'is_read' => 1 ],
			[
				'id'      => $id,
				'user_id' => $user_id,
			],
			[ '%d' ],
			[ '%d', '%d' ]
		);
		return is_int( $updated ) && $updated > 0;
	}

	/**
	 * Marks every unread notification of a user as read.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return int Rows updated.
	 */
	public function mark_all_read( int $user_id ): int {
		global $wpdb;
		$updated = $wpdb->update(
			$this->table(),
			[ 'is_read' => 1 ],
			[
				'user_id' => $user_id,
				'is_read' => 0,
			],
			[ '%d' ],
			[ '%d', '%d' ]
		);
		return is_int( $updated ) ? $updated : 0;
	}

	/**
	 * Deletes read notifications older than N days. Used by the daily cron.
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Retention window in days.
	 * @return int Rows deleted.
	 */
	public function purge_read_older_than( int $days ): int {
		global $wpdb;
		$table  = $this->table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, $days ) * DAY_IN_SECONDS );
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE is_read = 1 AND created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name only.
				$cutoff
			)
		);
		return is_int( $deleted ) ? $deleted : 0;
	}
}

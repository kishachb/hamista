<?php
/**
 * Public API of hamista-license-manager (spec §7, §14).
 *
 * Every function is a thin wrapper around a class. Guarded with
 * function_exists() so a stray double-load never fatals.
 *
 * @package Hamista\License
 */

defined( 'ABSPATH' ) || exit;

use Hamista\License\Data\Release_Repository;
use Hamista\License\License_Service;
use Hamista\License\Plugin;
use Hamista\Core\Support\Private_Storage;

if ( ! function_exists( 'hamista_lm' ) ) {
	/**
	 * The license manager plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	function hamista_lm(): Plugin {
		return Plugin::instance();
	}
}

if ( ! function_exists( 'hamista_lm_service' ) ) {
	/**
	 * The license service.
	 *
	 * @since 1.0.0
	 *
	 * @return License_Service
	 */
	function hamista_lm_service(): License_Service {
		return Plugin::instance()->service();
	}
}

if ( ! function_exists( 'hamista_lm_get_license' ) ) {
	/**
	 * A single license by id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id License id.
	 * @return array|null Value array (see Data\License_Repository), or null.
	 */
	function hamista_lm_get_license( int $id ): ?array {
		return Plugin::instance()->licenses()->find( $id );
	}
}

if ( ! function_exists( 'hamista_lm_get_licenses_for_user' ) ) {
	/**
	 * A user's licenses.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id User id.
	 * @param array $args    `status`, `orderby`, `order` — see Data\License_Repository::for_user().
	 * @return array<int, array>
	 */
	function hamista_lm_get_licenses_for_user( int $user_id, array $args = [] ): array {
		return Plugin::instance()->licenses()->for_user( $user_id, $args );
	}
}

if ( ! function_exists( 'hamista_lm_create_release' ) ) {
	/**
	 * Creates a release from a file already on disk, copying it into the
	 * plugin's private "releases" storage bucket (ruling R7; used by the
	 * core demo importer to seed placeholder releases).
	 *
	 * @since 1.0.0
	 *
	 * @param array  $args {
	 *     Release fields. `product_id` and `version` are required.
	 *
	 *     @type int    $product_id
	 *     @type string $version
	 *     @type string $channel      Default 'stable'.
	 *     @type string $status       Default 'published'.
	 *     @type string $changelog
	 *     @type string $requires_wp
	 *     @type string $tested_wp
	 *     @type string $requires_php
	 * }
	 * @param string $file_path Absolute path of the release ZIP to copy in.
	 * @return int|\WP_Error The new release id, or a WP_Error.
	 */
	function hamista_lm_create_release( array $args, string $file_path ) {
		if ( empty( $args['product_id'] ) || empty( $args['version'] ) ) {
			return new \WP_Error( 'hamista_lm_invalid_release', __( 'A release needs a product_id and a version.', 'hamista-license-manager' ) );
		}
		if ( ! is_file( $file_path ) || ! is_readable( $file_path ) ) {
			return new \WP_Error( 'hamista_lm_release_file_missing', __( 'The release file could not be read.', 'hamista-license-manager' ) );
		}

		$storage = new Private_Storage( \Hamista\License\Installer::RELEASES_BUCKET );
		$stored  = $storage->store_file(
			$file_path,
			basename( $file_path ),
			[
				'extensions' => array_map( 'trim', explode( ',', (string) hamista_get_option( 'hamista_licenses', 'allowed_extensions', 'zip' ) ) ),
				'max_size'   => (int) hamista_get_option( 'hamista_licenses', 'max_upload_mb', 100 ) * MB_IN_BYTES,
			]
		);
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		$release_id = ( new Release_Repository() )->create(
			[
				'product_id'   => (int) $args['product_id'],
				'version'      => (string) $args['version'],
				'channel'      => (string) ( $args['channel'] ?? 'stable' ),
				'status'       => (string) ( $args['status'] ?? 'published' ),
				'changelog'    => (string) ( $args['changelog'] ?? '' ),
				'file_name'    => $stored['name'],
				'file_size'    => $stored['size'],
				'file_hash'    => hash_file( 'sha256', $file_path ),
				'requires_wp'  => (string) ( $args['requires_wp'] ?? '' ),
				'tested_wp'    => (string) ( $args['tested_wp'] ?? '' ),
				'requires_php' => (string) ( $args['requires_php'] ?? '' ),
				'released_at'  => current_time( 'mysql', true ),
			]
		);

		if ( 0 === $release_id ) {
			return new \WP_Error( 'hamista_lm_release_not_saved', __( 'The release could not be saved.', 'hamista-license-manager' ) );
		}

		/**
		 * Fires when a release is published (ruling R6).
		 *
		 * @since 1.0.0
		 *
		 * @param array $release `id, product_id, version, channel, changelog, released_at` (UTC 'Y-m-d H:i:s').
		 */
		do_action(
			'hamista_release_published',
			[
				'id'          => $release_id,
				'product_id'  => (int) $args['product_id'],
				'version'     => (string) $args['version'],
				'channel'     => (string) ( $args['channel'] ?? 'stable' ),
				'changelog'   => (string) ( $args['changelog'] ?? '' ),
				'released_at' => current_time( 'mysql', true ),
			]
		);

		return $release_id;
	}
}

<?php
/**
 * Private file storage outside public access.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Stores files in a private bucket and streams them back after the owning
 * package has checked permissions.
 *
 * Layout: `{base}/{bucket}/[{subdir}/]{random}.{ext}`, where `{base}` is the
 * `HAMISTA_PRIVATE_STORAGE_PATH` constant or `uploads/hamista-private`.
 * The base and each bucket carry `.htaccess`, `web.config` and `index.php`
 * guards. On nginx the guards do nothing: define `HAMISTA_PRIVATE_STORAGE_PATH`
 * outside the web root, or deny the directory in the server config.
 *
 * Stored files are described by a record (spec §14):
 * `{ name, path (relative to the bucket), size, mime, uploaded_at (UTC) }`.
 * Never print `path` in HTML.
 *
 * @since 1.0.0
 */
final class Private_Storage {

	/**
	 * Extensions accepted when the rules don't list any.
	 */
	public const DEFAULT_EXTENSIONS = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'zip', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'psd', 'ai' ];

	/**
	 * File name segments that are never accepted, wherever they appear.
	 */
	private const BLOCKED_SEGMENTS = [ 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'htaccess', 'htpasswd', 'ini' ];

	/**
	 * Real MIME types accepted per extension. Several entries cover the ways
	 * fileinfo reports the same format (legacy Office files, Illustrator).
	 */
	private const MIME_TYPES = [
		'jpg'  => [ 'image/jpeg' ],
		'jpeg' => [ 'image/jpeg' ],
		'png'  => [ 'image/png' ],
		'gif'  => [ 'image/gif' ],
		'webp' => [ 'image/webp' ],
		'pdf'  => [ 'application/pdf' ],
		'zip'  => [ 'application/zip' ],
		'txt'  => [ 'text/plain', 'text/csv' ],
		'doc'  => [ 'application/msword', 'application/vnd.ms-office', 'application/CDFV2' ],
		'docx' => [ 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ],
		'xls'  => [ 'application/vnd.ms-excel', 'application/vnd.ms-office', 'application/CDFV2' ],
		'xlsx' => [ 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ],
		'ppt'  => [ 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/CDFV2' ],
		'pptx' => [ 'application/vnd.openxmlformats-officedocument.presentationml.presentation' ],
		'psd'  => [ 'image/vnd.adobe.photoshop' ],
		'ai'   => [ 'application/pdf', 'application/postscript' ],
	];

	/**
	 * Bytes streamed per read in send().
	 */
	private const CHUNK_SIZE = 1048576;

	/**
	 * Bucket slug.
	 *
	 * @var string
	 */
	private string $bucket;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket Bucket slug: lower-case letters, digits and dashes.
	 * @throws \InvalidArgumentException When the slug has other characters.
	 */
	public function __construct( string $bucket ) {
		if ( 1 !== preg_match( '/^[a-z0-9-]+$/', $bucket ) ) {
			throw new \InvalidArgumentException( 'A private storage bucket slug may only contain a-z, 0-9 and dashes.' );
		}
		$this->bucket = $bucket;
	}

	/**
	 * Base directory shared by every bucket.
	 *
	 * @since 1.0.0
	 *
	 * @return string Absolute path, no trailing slash.
	 */
	public static function base_path(): string {
		if ( defined( 'HAMISTA_PRIVATE_STORAGE_PATH' ) && '' !== (string) HAMISTA_PRIVATE_STORAGE_PATH ) {
			return untrailingslashit( (string) HAMISTA_PRIVATE_STORAGE_PATH );
		}
		$uploads = wp_upload_dir( null, false );
		return untrailingslashit( $uploads['basedir'] ) . '/hamista-private';
	}

	/**
	 * This bucket's directory.
	 *
	 * @since 1.0.0
	 *
	 * @return string Absolute path, no trailing slash.
	 */
	public function base_dir(): string {
		return self::base_path() . '/' . $this->bucket;
	}

	/**
	 * Creates the base and bucket directories and writes their guard files.
	 *
	 * @since 1.0.0
	 *
	 * @return bool False when a directory or guard file could not be written.
	 */
	public function ensure_protected(): bool {
		return self::protect_directory( self::base_path() ) && self::protect_directory( $this->base_dir() );
	}

	/**
	 * Creates a directory and writes (or repairs) its guard files.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Absolute directory path.
	 * @return bool
	 */
	public static function protect_directory( string $dir ): bool {
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$ok = true;
		foreach ( self::guard_files() as $name => $contents ) {
			$file = trailingslashit( $dir ) . $name;
			if ( is_file( $file ) && file_get_contents( $file ) === $contents ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
				continue;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- uploads dir; WP_Filesystem may need credentials.
			$ok = ( false !== file_put_contents( $file, $contents ) ) && $ok;
		}
		return $ok;
	}

	/**
	 * Validates an uploaded or server-side file against the rules.
	 *
	 * Rules: `max_size` (bytes, 0 = no limit; default: the upload limit),
	 * `extensions` (allow-list; default DEFAULT_EXTENSIONS) and `mimes`
	 * (`ext => mime|mime[]`, overrides the real-type map).
	 *
	 * @since 1.0.0
	 *
	 * @param array $file  A `$_FILES` entry: name, tmp_name, error.
	 * @param array $rules Validation rules.
	 * @return bool|\WP_Error True when valid.
	 */
	public function validate( array $file, array $rules = [] ) {
		$info = $this->inspect( $file, $rules );
		return is_wp_error( $info ) ? $info : true;
	}

	/**
	 * Validates and moves an HTTP upload into the bucket.
	 *
	 * Rules: see validate(), plus `subdir` (e.g. 'tickets/42').
	 *
	 * @since 1.0.0
	 *
	 * @param array $file  A `$_FILES` entry.
	 * @param array $rules Rules.
	 * @return array|\WP_Error The file record.
	 */
	public function store_upload( array $file, array $rules = [] ) {
		$info = $this->inspect( $file, $rules );
		if ( is_wp_error( $info ) ) {
			return $info;
		}
		if ( ! is_uploaded_file( $info['tmp'] ) ) {
			return new \WP_Error( 'hamista_file_not_uploaded', __( 'The file was not received through an upload form.', 'hamista-core' ) );
		}
		return $this->persist( $info, $rules, 'move_uploaded_file' );
	}

	/**
	 * Validates and copies a server-side file into the bucket.
	 *
	 * Rules: see validate() (`max_size` defaults to no limit here), plus `subdir`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $source Absolute path of the file to copy.
	 * @param string $name   Display name, which decides the expected type.
	 * @param array  $rules  Rules.
	 * @return array|\WP_Error The file record.
	 */
	public function store_file( string $source, string $name, array $rules = [] ) {
		$rules += [ 'max_size' => 0 ];
		$info   = $this->inspect(
			[
				'name'     => $name,
				'tmp_name' => $source,
				'error'    => UPLOAD_ERR_OK,
			],
			$rules
		);
		if ( is_wp_error( $info ) ) {
			return $info;
		}
		return $this->persist( $info, $rules, 'copy' );
	}

	/**
	 * Absolute path of a stored file, confined to this bucket.
	 *
	 * Only names this class generates (`{32 hex}.{ext}`) resolve, so a record
	 * can never point at a guard file or anything else in the bucket.
	 *
	 * @since 1.0.0
	 *
	 * @param array $record File record.
	 * @return string|false False when the file is missing, outside the bucket or not a stored file.
	 */
	public function path( array $record ): string|false {
		$relative = isset( $record['path'] ) && is_string( $record['path'] ) ? $record['path'] : '';
		// realpath() throws on NUL bytes.
		if ( '' === $relative || str_contains( $relative, "\0" ) || 1 !== preg_match( '/^[a-f0-9]{32}\.[a-z0-9]+$/', basename( $relative ) ) ) {
			return false;
		}

		$bucket = realpath( $this->base_dir() );
		$file   = realpath( $this->base_dir() . '/' . ltrim( $relative, '/\\' ) );
		if ( false === $bucket || false === $file || ! is_file( $file ) ) {
			return false;
		}
		return str_starts_with( $file, $bucket . DIRECTORY_SEPARATOR ) ? $file : false;
	}

	/**
	 * Streams a stored file to the browser and exits.
	 *
	 * The caller must have checked that the current user may read the file.
	 * Answers 404 (through wp_die()) when the file is missing.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $record      File record.
	 * @param string $disposition 'attachment' (default) or 'inline'.
	 */
	public function send( array $record, string $disposition = 'attachment' ): never {
		$file = $this->path( $record );
		if ( false === $file ) {
			self::die_with( __( 'The requested file is not available.', 'hamista-core' ), 404 );
		}

		$handle = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming a local file.
		if ( false === $handle ) {
			self::die_with( __( 'The requested file could not be read.', 'hamista-core' ), 500 );
		}

		$inline = 'inline' === $disposition;
		$name   = isset( $record['name'] ) && is_string( $record['name'] ) && '' !== $record['name'] ? $record['name'] : basename( $file );
		$mime   = isset( $record['mime'] ) && is_string( $record['mime'] ) && 1 === preg_match( '#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', $record['mime'] ) ? $record['mime'] : 'application/octet-stream';

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		status_header( 200 );
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . (string) filesize( $file ) );
		header( 'Content-Disposition: ' . self::content_disposition( $inline ? 'inline' : 'attachment', $name ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: private, no-store' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		if ( $inline ) {
			// Stops HTML/SVG content from running scripts in the site's origin.
			header( "Content-Security-Policy: default-src 'none'; sandbox" );
		}

		while ( ! feof( $handle ) ) {
			$chunk = fread( $handle, self::CHUNK_SIZE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			if ( false === $chunk ) {
				break;
			}
			echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw file bytes.
			flush();
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Deletes a stored file.
	 *
	 * @since 1.0.0
	 *
	 * @param array $record File record.
	 * @return bool True when the file is gone.
	 */
	public function delete( array $record ): bool {
		$file = $this->path( $record );
		if ( false === $file ) {
			return false;
		}
		wp_delete_file( $file );
		return ! file_exists( $file );
	}

	/**
	 * Whether a file name must be rejected. Pure: no WordPress needed.
	 *
	 * Blocks dot-files, names that are not plain file names (path
	 * separators, control bytes, `:` stream syntax), and any name with a
	 * dot-separated segment in BLOCKED_SEGMENTS, case-insensitively and in
	 * any position (`x.php.jpg`, `a.PhP`, `php.ini`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Original file name.
	 * @return bool
	 */
	public static function is_blocked_filename( string $name ): bool {
		if ( '' === trim( $name ) || 1 === preg_match( '#[/\\\\:\x00-\x1F\x7F]#', $name ) ) {
			return true;
		}
		if ( str_starts_with( ltrim( $name ), '.' ) ) {
			return true;
		}
		foreach ( explode( '.', strtolower( $name ) ) as $segment ) {
			if ( in_array( trim( $segment ), self::BLOCKED_SEGMENTS, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks a file and resolves its real type.
	 *
	 * @param array $file  name, tmp_name, error.
	 * @param array $rules Rules.
	 * @return array|\WP_Error `[ name, ext, mime, size, tmp ]`.
	 */
	private function inspect( array $file, array $rules ) {
		$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_OK;
		if ( UPLOAD_ERR_OK !== $error ) {
			return new \WP_Error( 'hamista_upload_error', self::upload_error_message( $error ) );
		}

		$name = isset( $file['name'] ) ? (string) $file['name'] : '';
		$tmp  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		if ( self::is_blocked_filename( $name ) ) {
			return new \WP_Error( 'hamista_file_name_blocked', __( 'This file name is not allowed.', 'hamista-core' ) );
		}
		if ( '' === $tmp || ! is_file( $tmp ) || ! is_readable( $tmp ) ) {
			return new \WP_Error( 'hamista_file_missing', __( 'The file could not be read.', 'hamista-core' ) );
		}

		$size = (int) filesize( $tmp );
		$max  = array_key_exists( 'max_size', $rules ) ? (int) $rules['max_size'] : (int) wp_max_upload_size();
		if ( $size <= 0 ) {
			return new \WP_Error( 'hamista_file_empty', __( 'The file is empty.', 'hamista-core' ) );
		}
		if ( $max > 0 && $size > $max ) {
			return new \WP_Error(
				'hamista_file_too_large',
				/* translators: %s: maximum file size, e.g. "8 MB". */
				sprintf( __( 'The file is too large. The maximum size is %s.', 'hamista-core' ), size_format( $max ) )
			);
		}

		$extensions = self::allowed_extensions( $rules );
		$extension  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, $extensions, true ) ) {
			return new \WP_Error(
				'hamista_file_type_not_allowed',
				/* translators: %s: comma-separated list of file extensions. */
				sprintf( __( 'This file type is not allowed. Allowed types: %s.', 'hamista-core' ), implode( ', ', $extensions ) )
			);
		}

		$real = self::real_type( $tmp, $name, $extension, self::mime_map( $extensions, (array) ( $rules['mimes'] ?? [] ) ) );
		if ( null === $real ) {
			return new \WP_Error( 'hamista_file_type_mismatch', __( 'The file content does not match its type.', 'hamista-core' ) );
		}

		return [
			'name' => sanitize_file_name( '' !== $real['proper_filename'] ? $real['proper_filename'] : $name ),
			'ext'  => $real['ext'],
			'mime' => $real['mime'],
			'size' => $size,
			'tmp'  => $tmp,
		];
	}

	/**
	 * Moves or copies a checked file into the bucket and builds its record.
	 *
	 * @param array    $info     Result of inspect().
	 * @param array    $rules    Rules (`subdir`).
	 * @param callable $transfer fn( string $from, string $to ): bool.
	 * @return array|\WP_Error
	 */
	private function persist( array $info, array $rules, callable $transfer ) {
		$subdir = self::sanitize_subdir( (string) ( $rules['subdir'] ?? '' ) );
		if ( ! $this->ensure_protected() ) {
			return new \WP_Error( 'hamista_storage_unwritable', __( 'The private storage directory is not writable.', 'hamista-core' ) );
		}

		$dir = $this->base_dir();
		foreach ( '' === $subdir ? [] : explode( '/', $subdir ) as $segment ) {
			$dir .= '/' . $segment;
			if ( ! wp_mkdir_p( $dir ) ) {
				return new \WP_Error( 'hamista_storage_unwritable', __( 'The private storage directory is not writable.', 'hamista-core' ) );
			}
			if ( ! is_file( $dir . '/index.php' ) ) {
				file_put_contents( $dir . '/index.php', self::guard_files()['index.php'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}

		$stored = bin2hex( random_bytes( 16 ) ) . '.' . $info['ext'];
		$target = $dir . '/' . $stored;
		if ( ! $transfer( $info['tmp'], $target ) ) {
			return new \WP_Error( 'hamista_file_store_failed', __( 'The file could not be saved.', 'hamista-core' ) );
		}
		chmod( $target, 0640 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod

		return [
			'name'        => $info['name'],
			'path'        => ( '' === $subdir ? '' : $subdir . '/' ) . $stored,
			'size'        => $info['size'],
			'mime'        => $info['mime'],
			'uploaded_at' => gmdate( 'Y-m-d H:i:s' ),
		];
	}

	/**
	 * Resolves the real type through wp_check_filetype_and_ext().
	 *
	 * Tries each accepted MIME type of the extension in turn. The accepted
	 * types are allowed through `upload_mimes` only while checking, because
	 * WordPress also requires the type to be globally allowed.
	 *
	 * @param string $tmp       File path.
	 * @param string $name      Original name.
	 * @param string $extension Lower-case extension of the original name.
	 * @param array  $map       `ext => mime[]` for every allowed extension.
	 * @return array{ext:string,mime:string,proper_filename:string}|null
	 */
	private static function real_type( string $tmp, string $name, string $extension, array $map ): ?array {
		if ( empty( $map[ $extension ] ) ) {
			return null;
		}

		$accepted = array_values( array_unique( array_merge( ...array_values( $map ) ) ) );
		$allow    = static function ( $mimes ) use ( $accepted ) {
			$mimes = (array) $mimes;
			foreach ( $accepted as $index => $mime ) {
				$mimes[ 'hamista_private_' . $index ] = $mime;
			}
			return $mimes;
		};

		add_filter( 'upload_mimes', $allow, PHP_INT_MAX );
		try {
			foreach ( $map[ $extension ] as $candidate ) {
				$mimes = [];
				foreach ( $map as $ext => $types ) {
					$mimes[ $ext ] = $types[0];
				}
				$mimes[ $extension ] = $candidate;

				$check = wp_check_filetype_and_ext( $tmp, $name, $mimes );
				$ext   = is_string( $check['ext'] ) ? strtolower( $check['ext'] ) : '';
				$type  = is_string( $check['type'] ) ? $check['type'] : '';
				if ( '' !== $ext && isset( $map[ $ext ] ) && in_array( $type, $map[ $ext ], true ) ) {
					return [
						'ext'             => $ext,
						'mime'            => $type,
						'proper_filename' => is_string( $check['proper_filename'] ) ? $check['proper_filename'] : '',
					];
				}
			}
		} finally {
			remove_filter( 'upload_mimes', $allow, PHP_INT_MAX );
		}
		return null;
	}

	/**
	 * Allowed extensions from the rules, lower-cased.
	 *
	 * @param array $rules Rules.
	 * @return string[]
	 */
	private static function allowed_extensions( array $rules ): array {
		$extensions = isset( $rules['extensions'] ) ? (array) $rules['extensions'] : self::DEFAULT_EXTENSIONS;
		$extensions = array_map( static fn( $ext ): string => strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $ext ) ), $extensions );
		return array_values( array_unique( array_filter( $extensions ) ) );
	}

	/**
	 * Accepted real MIME types per allowed extension.
	 *
	 * @param string[] $extensions Allowed extensions.
	 * @param array    $custom     `ext => mime|mime[]` overrides.
	 * @return array<string, string[]> Extensions without a known type are left out (rejected).
	 */
	private static function mime_map( array $extensions, array $custom ): array {
		$map = [];
		foreach ( $extensions as $ext ) {
			if ( isset( $custom[ $ext ] ) ) {
				$map[ $ext ] = array_values( array_filter( array_map( 'strval', (array) $custom[ $ext ] ) ) );
			} elseif ( isset( self::MIME_TYPES[ $ext ] ) ) {
				$map[ $ext ] = self::MIME_TYPES[ $ext ];
			} else {
				foreach ( wp_get_mime_types() as $pattern => $mime ) {
					if ( in_array( $ext, explode( '|', $pattern ), true ) ) {
						$map[ $ext ] = [ $mime ];
						break;
					}
				}
			}
		}
		return array_filter( $map );
	}

	/**
	 * Reduces a subdirectory to safe `[a-z0-9_-]` segments.
	 *
	 * @param string $subdir Requested subdirectory, e.g. 'tickets/42'.
	 * @return string Segments joined by '/', or ''.
	 */
	private static function sanitize_subdir( string $subdir ): string {
		$segments = array_map(
			static fn( string $segment ): string => (string) preg_replace( '/[^a-z0-9_-]/', '', strtolower( $segment ) ),
			explode( '/', str_replace( '\\', '/', $subdir ) )
		);
		return implode( '/', array_filter( $segments, static fn( string $segment ): bool => '' !== $segment ) );
	}

	/**
	 * Content-Disposition value with an ASCII fallback and an RFC 5987 UTF-8 name.
	 *
	 * @param string $type 'inline' or 'attachment'.
	 * @param string $name File name.
	 * @return string
	 */
	private static function content_disposition( string $type, string $name ): string {
		$name  = str_replace( [ "\r", "\n", '"', '\\', '/' ], '', $name );
		$ascii = trim( (string) preg_replace( [ '/[^A-Za-z0-9._-]+/', '/-{2,}/' ], '-', remove_accents( $name ) ), '-' );
		if ( '' === trim( pathinfo( $ascii, PATHINFO_FILENAME ), '._-' ) ) {
			// Nothing readable is left (e.g. a Persian name): keep the extension only.
			$extension = strtolower( (string) preg_replace( '/[^A-Za-z0-9]/', '', pathinfo( $name, PATHINFO_EXTENSION ) ) );
			$ascii     = 'download' . ( '' === $extension ? '' : '.' . $extension );
		}
		return sprintf( '%s; filename="%s"; filename*=UTF-8\'\'%s', $type, $ascii, rawurlencode( $name ) );
	}

	/**
	 * Ends the request with an error page.
	 *
	 * @param string $message Translated message.
	 * @param int    $status  HTTP status.
	 */
	private static function die_with( string $message, int $status ): never {
		wp_die( esc_html( $message ), '', [ 'response' => (int) $status ] );
		exit; // In case a custom wp_die handler returns.
	}

	/**
	 * Translated message for a PHP upload error code.
	 *
	 * @param int $error UPLOAD_ERR_* code.
	 * @return string
	 */
	private static function upload_error_message( int $error ): string {
		switch ( $error ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'The file is larger than the maximum upload size.', 'hamista-core' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'The file was only partly uploaded. Please try again.', 'hamista-core' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'No file was uploaded.', 'hamista-core' );
			default:
				return __( 'The file could not be uploaded.', 'hamista-core' );
		}
	}

	/**
	 * Guard files written into protected directories.
	 *
	 * @return array<string, string> File name => contents.
	 */
	private static function guard_files(): array {
		return [
			'.htaccess'  => "# Hamista private storage: no direct web access.\n"
				. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n",
			'index.php'  => "<?php\n// Silence is golden.\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
				. "<!-- Hamista private storage: no direct web access. -->\n"
				. "<configuration>\n\t<system.webServer>\n"
				. "\t\t<security>\n"
				. "\t\t\t<authorization>\n\t\t\t\t<remove users=\"*\" roles=\"\" verbs=\"\" />\n\t\t\t\t<add accessType=\"Deny\" users=\"*\" />\n\t\t\t</authorization>\n"
				. "\t\t\t<requestFiltering>\n\t\t\t\t<fileExtensions allowUnlisted=\"false\" />\n\t\t\t</requestFiltering>\n"
				. "\t\t</security>\n"
				. "\t</system.webServer>\n</configuration>\n",
		];
	}
}

<?php
/**
 * Value object returned by every License_Service method (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * An immutable success/failure result with a machine-readable code, a
 * human-readable message and a data payload.
 *
 * Build it with Result::ok() or Result::error(); the constructor is private
 * so every instance carries a well-formed http_status.
 *
 * @since 1.0.0
 */
final class Result {

	/**
	 * Whether the operation succeeded.
	 *
	 * @var bool
	 */
	public readonly bool $success;

	/**
	 * Machine-readable code, e.g. 'activated' or 'license_not_found'.
	 *
	 * @var string
	 */
	public readonly string $code;

	/**
	 * Human-readable message.
	 *
	 * @var string
	 */
	public readonly string $message;

	/**
	 * Payload, e.g. `[ 'license' => [...] ]`.
	 *
	 * @var array<string, mixed>
	 */
	public readonly array $data;

	/**
	 * HTTP status to answer a REST request with.
	 *
	 * @var int
	 */
	private readonly int $status;

	/**
	 * Private constructor: use ok() or error().
	 *
	 * @param bool                 $success Whether the operation succeeded.
	 * @param string               $code    Machine-readable code.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $data    Payload.
	 * @param int                  $status  HTTP status.
	 */
	private function __construct( bool $success, string $code, string $message, array $data, int $status ) {
		$this->success = $success;
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
		$this->status  = $status;
	}

	/**
	 * Builds a successful result (HTTP 200).
	 *
	 * @since 1.0.0
	 *
	 * @param string               $code    Machine-readable code, e.g. 'activated'.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $data    Payload, e.g. `[ 'license' => [...] ]`.
	 * @return Result
	 */
	public static function ok( string $code, string $message, array $data = [] ): Result {
		return new self( true, $code, $message, $data, 200 );
	}

	/**
	 * Builds a failed result.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $code        Machine-readable error code, e.g. 'license_not_found'.
	 * @param string               $message     Human-readable message.
	 * @param int                  $http_status HTTP status for a REST response.
	 * @param array<string, mixed> $data        Extra payload.
	 * @return Result
	 */
	public static function error( string $code, string $message, int $http_status = 400, array $data = [] ): Result {
		return new self( false, $code, $message, $data, $http_status );
	}

	/**
	 * Plain array shape for a REST response body: `success`, `code`, `message`, `data`.
	 *
	 * @since 1.0.0
	 *
	 * @return array{success:bool,code:string,message:string,data:array<string,mixed>}
	 */
	public function to_array(): array {
		return [
			'success' => $this->success,
			'code'    => $this->code,
			'message' => $this->message,
			'data'    => $this->data,
		];
	}

	/**
	 * HTTP status to answer a REST request with.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function http_status(): int {
		return $this->status;
	}
}

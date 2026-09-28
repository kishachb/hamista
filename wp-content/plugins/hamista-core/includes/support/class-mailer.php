<?php
/**
 * Branded HTML email.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Renders `templates/emails/base.php` and sends it with wp_mail().
 *
 * The layout is RTL-aware: `dir` and `lang` come from the locale the email
 * is rendered in (the recipient's, when `user_id` is given). Themes can
 * override it at `{theme}/hamista-core/emails/base.php`.
 *
 * @since 1.0.0
 */
final class Mailer {

	/**
	 * Languages written right to left, for locales without a WordPress language pack.
	 */
	private const RTL_LANGUAGES = [ 'ar', 'arc', 'ary', 'azb', 'ckb', 'dv', 'fa', 'haz', 'he', 'ps', 'sd', 'ug', 'ur', 'yi' ];

	/**
	 * Sends a branded HTML email.
	 *
	 * @since 1.0.0
	 *
	 * @param string|string[] $to      Recipient(s).
	 * @param string          $subject Subject (plain text).
	 * @param array           $args    {
	 *     Email content.
	 *
	 *     @type string $heading Main heading (plain text).
	 *     @type string $body    HTML, filtered with wp_kses_post().
	 *     @type array  $button  { text, url } call to action; both required to show it.
	 *     @type string $footer  HTML footer; defaults to a line with the site name.
	 *     @type int    $user_id Recipient user; the email renders in their locale.
	 * }
	 * @return bool Whether wp_mail() accepted the message.
	 */
	public function send( $to, string $subject, array $args ): bool {
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$from    = $this->from_header();
		if ( '' !== $from ) {
			$headers[] = $from;
		}
		return (bool) wp_mail( $to, $subject, $this->render( $subject, $args ), $headers );
	}

	/**
	 * Renders the email HTML without sending it (also used for previews).
	 *
	 * @since 1.0.0
	 *
	 * @param string $subject Subject, used as the document title.
	 * @param array  $args    See send().
	 * @return string
	 */
	public function render( string $subject, array $args ): string {
		$args     = $this->normalize( $args );
		$switched = $args['user_id'] > 0 && switch_to_user_locale( $args['user_id'] );

		try {
			$locale = determine_locale();
			$html   = Template_Loader::render(
				'hamista-core',
				'emails/base.php',
				[
					'subject'   => $subject,
					'heading'   => $args['heading'],
					'body'      => $args['body'],
					'button'    => $args['button'],
					'footer'    => $args['footer'],
					'dir'       => self::is_rtl_locale( $locale ) ? 'rtl' : 'ltr',
					'lang'      => str_replace( '_', '-', $locale ),
					'logo_url'  => $this->logo_url(),
					'site_name' => wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ),
					'site_url'  => home_url( '/' ),
				],
				HAMISTA_CORE_PATH . 'templates/'
			);
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}
		}
		return $html;
	}

	/**
	 * Whether a locale is written right to left.
	 *
	 * @param string $locale Locale, e.g. 'fa_IR'.
	 * @return bool
	 */
	private static function is_rtl_locale( string $locale ): bool {
		return is_rtl() || in_array( strtolower( (string) strtok( $locale, '_' ) ), self::RTL_LANGUAGES, true );
	}

	/**
	 * Fills in missing arguments.
	 *
	 * @param array $args Raw arguments.
	 * @return array{heading:string,body:string,button:array{text:string,url:string},footer:string,user_id:int}
	 */
	private function normalize( array $args ): array {
		$button = isset( $args['button'] ) && is_array( $args['button'] ) ? $args['button'] : [];
		return [
			'heading' => (string) ( $args['heading'] ?? '' ),
			'body'    => (string) ( $args['body'] ?? '' ),
			'button'  => [
				'text' => (string) ( $button['text'] ?? '' ),
				'url'  => (string) ( $button['url'] ?? '' ),
			],
			'footer'  => (string) ( $args['footer'] ?? '' ),
			'user_id' => absint( $args['user_id'] ?? 0 ),
		];
	}

	/**
	 * Logo URL from the `email_logo` setting (attachment ID or URL).
	 *
	 * @return string '' when no logo is set.
	 */
	private function logo_url(): string {
		$logo = Options::get( 'hamista_core', 'email_logo', 0 );
		if ( is_numeric( $logo ) && (int) $logo > 0 ) {
			return (string) wp_get_attachment_image_url( (int) $logo, 'medium' );
		}
		return is_string( $logo ) ? esc_url_raw( $logo ) : '';
	}

	/**
	 * From header, only when `mail_from_email` is configured.
	 *
	 * @return string
	 */
	private function from_header(): string {
		$email = sanitize_email( (string) Options::get( 'hamista_core', 'mail_from_email', '' ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return '';
		}
		$name = (string) Options::get( 'hamista_core', 'mail_from_name', '' );
		if ( '' === trim( $name ) ) {
			$name = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
		}
		$name = trim( str_replace( [ "\r", "\n", '"', '<', '>' ], '', $name ) );
		return sprintf( 'From: "%s" <%s>', $name, $email );
	}
}

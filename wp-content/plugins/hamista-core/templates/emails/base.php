<?php
/**
 * Branded HTML email layout.
 *
 * Table layout with inline styles for email clients. Override it in a theme
 * at `hamista-core/emails/base.php`.
 *
 * @package Hamista\Core
 *
 * @var array $args {
 *     @type string $subject   Subject (document title).
 *     @type string $heading   Heading, plain text.
 *     @type string $body      Body HTML (filtered with wp_kses_post() here).
 *     @type array  $button    { text, url }.
 *     @type string $footer    Footer HTML, or '' for the default line.
 *     @type string $dir       'rtl' or 'ltr'.
 *     @type string $lang      BCP 47 language tag, e.g. 'fa-IR'.
 *     @type string $logo_url  Logo URL, or '' to show the site name.
 *     @type string $site_name Site name.
 *     @type string $site_url  Home URL.
 * }
 */

defined( 'ABSPATH' ) || exit;

$hamista_rtl   = 'rtl' === $args['dir'];
$hamista_align = $hamista_rtl ? 'right' : 'left';
$hamista_font  = $hamista_rtl ? "Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif" : "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif";
$hamista_line  = $hamista_rtl ? '1.9' : '1.65';
// The vivid brand gradient starts at the inline-start edge.
$hamista_brand_gradient = 'linear-gradient(' . ( $hamista_rtl ? '270deg' : '90deg' ) . ',#FF7A1A,#FF3B5C,#8B5CF6)';
$hamista_button         = $args['button'];
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $args['lang'] ); ?>" dir="<?php echo esc_attr( $args['dir'] ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light">
	<meta name="supported-color-schemes" content="light">
	<title><?php echo esc_html( $args['subject'] ); ?></title>
	<style>
		a { color: #C2410C; }
		@media (max-width: 620px) { .hm-email-pad { padding-left: 24px !important; padding-right: 24px !important; } }
	</style>
</head>
<body style="margin:0;padding:0;background-color:#F4F4F6;-webkit-text-size-adjust:100%;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="<?php echo esc_attr( $args['dir'] ); ?>" style="background-color:#F4F4F6;">
		<tr>
			<td align="center" style="padding:32px 12px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background-color:#FFFFFF;border:1px solid #E6E6EC;border-radius:16px;overflow:hidden;">
					<tr>
						<td style="height:6px;line-height:6px;font-size:0;background-color:#FF3B5C;background-image:<?php echo esc_attr( $hamista_brand_gradient ); ?>;">&nbsp;</td>
					</tr>
					<tr>
						<td class="hm-email-pad" style="padding:32px 40px 8px;text-align:<?php echo esc_attr( $hamista_align ); ?>;font-family:<?php echo esc_attr( $hamista_font ); ?>;">
							<a href="<?php echo esc_url( $args['site_url'] ); ?>" style="text-decoration:none;color:#16161D;">
								<?php if ( '' !== $args['logo_url'] ) : ?>
									<img src="<?php echo esc_url( $args['logo_url'] ); ?>" alt="<?php echo esc_attr( $args['site_name'] ); ?>" width="140" style="display:inline-block;width:140px;max-width:100%;height:auto;border:0;">
								<?php else : ?>
									<span style="font-size:20px;font-weight:800;color:#16161D;"><?php echo esc_html( $args['site_name'] ); ?></span>
								<?php endif; ?>
							</a>
						</td>
					</tr>
					<tr>
						<td class="hm-email-pad" style="padding:16px 40px 8px;text-align:<?php echo esc_attr( $hamista_align ); ?>;font-family:<?php echo esc_attr( $hamista_font ); ?>;color:#16161D;">
							<?php if ( '' !== $args['heading'] ) : ?>
								<h1 style="margin:0 0 16px;font-size:22px;line-height:1.5;font-weight:800;color:#16161D;"><?php echo esc_html( $args['heading'] ); ?></h1>
							<?php endif; ?>
							<div style="font-size:15px;line-height:<?php echo esc_attr( $hamista_line ); ?>;color:#3A3A48;">
								<?php echo wp_kses_post( $args['body'] ); ?>
							</div>
						</td>
					</tr>
					<?php if ( '' !== $hamista_button['text'] && '' !== $hamista_button['url'] ) : ?>
						<tr>
							<td class="hm-email-pad" style="padding:16px 40px 8px;text-align:<?php echo esc_attr( $hamista_align ); ?>;font-family:<?php echo esc_attr( $hamista_font ); ?>;">
								<a href="<?php echo esc_url( $hamista_button['url'] ); ?>" style="display:inline-block;padding:14px 28px;border-radius:12px;background-color:#BE123C;background-image:linear-gradient(135deg,#C2410C,#BE123C,#7C3AED);color:#FFFFFF;font-size:15px;font-weight:700;line-height:1.2;text-decoration:none;"><?php echo esc_html( $hamista_button['text'] ); ?></a>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td style="height:32px;line-height:32px;font-size:0;">&nbsp;</td>
					</tr>
					<tr>
						<td class="hm-email-pad" style="padding:20px 40px;background-color:#F7F7F8;border-top:1px solid #E6E6EC;text-align:<?php echo esc_attr( $hamista_align ); ?>;font-family:<?php echo esc_attr( $hamista_font ); ?>;font-size:13px;line-height:1.8;color:#5B5B6B;">
							<?php
							if ( '' !== $args['footer'] ) {
								echo wp_kses_post( $args['footer'] );
							} else {
								printf(
									/* translators: %s: linked site name. */
									esc_html__( 'This email was sent by %s.', 'hamista-core' ),
									'<a href="' . esc_url( $args['site_url'] ) . '" style="color:#5B5B6B;">' . esc_html( $args['site_name'] ) . '</a>'
								);
							}
							?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>

<?php
/**
 * Default Hamista landing page (replaced by the admin-dashboard Overview).
 *
 * @package Hamista\Core
 *
 * @var array $args {
 *     @type string $settings_url Settings page URL, or '' without manage_options.
 *     @type string $modules_url  Modules tab URL, or ''.
 *     @type string $docs_url     Documentation URL.
 *     @type int    $modules      Registered modules.
 *     @type int    $active       Booted modules.
 *     @type string $version      Core version.
 * }
 */

use Hamista\Core\Support\Html;

defined( 'ABSPATH' ) || exit;

$hamista_svg   = Html::svg_kses();
$hamista_cards = [
	[
		'icon'  => 'settings',
		'title' => __( 'Settings', 'hamista-core' ),
		'text'  => __( 'Brand, colours, header, footer, shop and every platform feature, in one panel.', 'hamista-core' ),
		'label' => __( 'Open settings', 'hamista-core' ),
		'url'   => $args['settings_url'],
	],
	[
		'icon'  => 'puzzle',
		'title' => __( 'Modules', 'hamista-core' ),
		'text'  => __( 'Switch features on or off. A module that is off loads no code.', 'hamista-core' ),
		'label' => __( 'Manage modules', 'hamista-core' ),
		'url'   => $args['modules_url'],
	],
	[
		'icon'     => 'book-open',
		'title'    => __( 'Documentation', 'hamista-core' ),
		'text'     => __( 'Installation and user guides, plus the developer reference.', 'hamista-core' ),
		'label'    => __( 'Read the docs', 'hamista-core' ),
		'url'      => $args['docs_url'],
		'external' => true,
	],
];
?>
<div class="wrap">
	<?php
	hamista_admin_header(
		__( 'Welcome to Hamista', 'hamista-core' ),
		[
			'subtitle' => __( 'Services, digital products and customer care, built on one platform.', 'hamista-core' ),
			'actions'  => '' === $args['settings_url'] ? [] : [
				[
					'label'   => __( 'Open settings', 'hamista-core' ),
					'url'     => $args['settings_url'],
					'primary' => true,
				],
			],
		]
	);
	?>

	<div class="hm-admin-grid">
		<div class="hm-stat">
			<span class="hm-stat__label"><?php esc_html_e( 'Hamista Core', 'hamista-core' ); ?></span>
			<span class="hm-stat__value"><?php echo esc_html( $args['version'] ); ?></span>
			<span class="hm-stat__meta"><?php esc_html_e( 'Installed version', 'hamista-core' ); ?></span>
			<span class="hm-stat__icon"><?php echo wp_kses( hamista_icon( 'shield-check' ), $hamista_svg ); ?></span>
		</div>
		<div class="hm-stat">
			<span class="hm-stat__label"><?php esc_html_e( 'Modules', 'hamista-core' ); ?></span>
			<span class="hm-stat__value">
				<?php
				/* translators: 1: number of active modules, 2: number of registered modules. */
				echo esc_html( sprintf( __( '%1$s of %2$s', 'hamista-core' ), number_format_i18n( $args['active'] ), number_format_i18n( $args['modules'] ) ) );
				?>
			</span>
			<span class="hm-stat__meta"><?php esc_html_e( 'Active', 'hamista-core' ); ?></span>
			<span class="hm-stat__icon"><?php echo wp_kses( hamista_icon( 'layers' ), $hamista_svg ); ?></span>
		</div>
		<div class="hm-stat">
			<span class="hm-stat__label"><?php esc_html_e( 'WooCommerce', 'hamista-core' ); ?></span>
			<span class="hm-stat__value">
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<span class="hm-badge hm-badge--success"><?php esc_html_e( 'Active', 'hamista-core' ); ?></span>
				<?php else : ?>
					<span class="hm-badge hm-badge--warning"><?php esc_html_e( 'Not active', 'hamista-core' ); ?></span>
				<?php endif; ?>
			</span>
			<span class="hm-stat__meta"><?php esc_html_e( 'Shop, licences and the customer panel need it.', 'hamista-core' ); ?></span>
			<span class="hm-stat__icon"><?php echo wp_kses( hamista_icon( 'store' ), $hamista_svg ); ?></span>
		</div>
	</div>

	<div class="hm-admin-grid">
		<?php foreach ( $hamista_cards as $hamista_card ) : ?>
			<div class="hm-card hm-card--hover">
				<span class="hm-card__icon"><?php echo wp_kses( hamista_icon( $hamista_card['icon'] ), $hamista_svg ); ?></span>
				<h2 class="hm-card__title"><?php echo esc_html( $hamista_card['title'] ); ?></h2>
				<p class="hm-card__text"><?php echo esc_html( $hamista_card['text'] ); ?></p>
				<?php if ( '' !== $hamista_card['url'] ) : ?>
					<div class="hm-card__footer">
						<?php if ( empty( $hamista_card['external'] ) ) : ?>
							<a class="button" href="<?php echo esc_url( $hamista_card['url'] ); ?>"><?php echo esc_html( $hamista_card['label'] ); ?></a>
						<?php else : ?>
							<a class="button" href="<?php echo esc_url( $hamista_card['url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $hamista_card['label'] ); ?>
								<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'hamista-core' ); ?></span>
								<?php echo wp_kses( hamista_icon( 'external-link' ), $hamista_svg ); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>

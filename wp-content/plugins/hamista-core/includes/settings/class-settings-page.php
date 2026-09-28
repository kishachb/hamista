<?php
/**
 * The "Hamista Settings" admin screen.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Settings;

use Hamista\Core\Admin\Admin_Menu;
use Hamista\Core\Plugin;
use Hamista\Core\Support\Html;
use Hamista\Core\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the settings screen, its save/reset/import/export handlers and
 * the built-in "Import / Export" tab.
 *
 * Page: submenu `hamista-settings` under `hamista` (position 90), plus
 * "Appearance → Theme Settings" pointing at the same page (its `theme`
 * group is preselected when there is no explicit `tab`).
 *
 * @since 1.0.0
 */
final class Settings_Page {

	/**
	 * Hook suffixes of the two menu entries this page is registered under.
	 *
	 * @var string[]
	 */
	private array $hook_suffixes = [];

	/**
	 * The registry for the current request (set by render() and the handlers).
	 *
	 * @var Settings_Registry|null
	 */
	private ?Settings_Registry $registry = null;

	/**
	 * Attaches every hook.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_post_hamista_save_settings', [ $this, 'handle_save' ] );
		add_action( 'admin_post_hamista_reset_settings_tab', [ $this, 'handle_reset' ] );
		add_action( 'admin_post_hamista_export_settings', [ $this, 'handle_export' ] );
		add_action( 'admin_post_hamista_import_settings', [ $this, 'handle_import' ] );
		add_action( 'hamista_register_settings', [ $this, 'register_import_export_tab' ], 90 );
	}

	/**
	 * Adds the two menu entries. Hooked to `admin_menu` priority 20 (after
	 * the `hamista` top-level menu, added at priority 9).
	 *
	 * @since 1.0.0
	 */
	public function add_menu(): void {
		$callback              = [ $this, 'render' ];
		$this->hook_suffixes[] = (string) add_submenu_page( Admin_Menu::SLUG, __( 'Settings', 'hamista-core' ), __( 'Settings', 'hamista-core' ), 'manage_options', Admin_Menu::SETTINGS_SLUG, $callback, 90 );
		$this->hook_suffixes[] = (string) add_theme_page( __( 'Theme Settings', 'hamista-core' ), __( 'Theme Settings', 'hamista-core' ), 'manage_options', Admin_Menu::SETTINGS_SLUG, $callback );
	}

	/**
	 * Registers and enqueues the settings assets, only on this screen.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $hook_suffix Current admin hook suffix.
	 */
	public function enqueue( $hook_suffix ): void {
		if ( ! in_array( (string) $hook_suffix, $this->hook_suffixes, true ) ) {
			return;
		}

		wp_enqueue_media();

		$assets = Plugin::instance()->assets();
		$assets->register_style( 'hamista-settings', 'assets/admin/settings.css', [ 'hamista-admin' ] );
		$assets->register_script( 'hamista-settings', 'assets/admin/settings.js', [ 'hamista-admin' ] );
		wp_enqueue_style( 'hamista-settings' );
		wp_enqueue_script( 'hamista-settings' );

		$config = [
			'i18n' => [
				'unsavedWarning' => __( 'You have unsaved changes. Leave this page anyway?', 'hamista-core' ),
				'selectImage'    => __( 'Select image', 'hamista-core' ),
				'useImage'       => __( 'Use this image', 'hamista-core' ),
				'confirm'        => __( 'Are you sure?', 'hamista-core' ),
				'addRow'         => __( 'Add row', 'hamista-core' ),
				'noResults'      => __( 'No settings match your search.', 'hamista-core' ),
			],
		];
		wp_add_inline_script( 'hamista-settings', 'window.hamistaSettings = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * Registers the built-in "Import / Export" tab. Priority 90, so it lands
	 * near the end of the `advanced` group but every other tab has already
	 * registered its option when export/import run.
	 *
	 * @since 1.0.0
	 *
	 * @param Settings_Registry $registry Registry.
	 */
	public function register_import_export_tab( Settings_Registry $registry ): void {
		$registry->add_tab(
			[
				'id'       => 'import-export',
				'title'    => __( 'Import / Export', 'hamista-core' ),
				'icon'     => 'download',
				'group'    => 'advanced',
				'option'   => '',
				'priority' => 60,
				'sections' => [
					[
						'id'     => 'main',
						'title'  => __( 'Import / Export', 'hamista-core' ),
						'fields' => [
							[
								'id'      => 'notice',
								'type'    => 'html',
								'content' => '<p>' . esc_html__( 'Export downloads every Hamista option as JSON, including module toggles. Import applies a previously exported file: every value is validated and sanitized the same way as a normal save, and unknown keys are ignored.', 'hamista-core' ) . '</p>',
							],
						],
					],
				],
			]
		);
	}

	/* --------------------------------------------------------------- page */

	/**
	 * Renders the settings screen.
	 *
	 * @since 1.0.0
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Hamista settings.', 'hamista-core' ), '', [ 'response' => 403 ] );
		}

		$this->registry = Plugin::instance()->settings();
		$tabs            = $this->registry->tabs();

		echo '<div class="wrap hamista-admin hm-settings" data-hm-settings>';
		hamista_admin_header( __( 'Hamista Settings', 'hamista-core' ), [ 'subtitle' => __( 'Theme, platform modules and advanced settings, all in one place.', 'hamista-core' ) ] );

		if ( [] === $tabs ) {
			echo '<div class="hm-empty"><p class="hm-empty__text">' . esc_html__( 'No settings are registered yet.', 'hamista-core' ) . '</p></div></div>';
			return;
		}

		$this->render_notice();

		$default_group = ( 'themes.php' === ( $GLOBALS['pagenow'] ?? '' ) ) ? 'theme' : '';
		$default_tab_id = $tabs[0]['id'];
		if ( '' !== $default_group ) {
			foreach ( $tabs as $candidate ) {
				if ( $default_group === $candidate['group'] ) {
					$default_tab_id = $candidate['id'];
					break;
				}
			}
		}

		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab selector.
		$tab       = '' !== $requested ? $this->registry->get_tab( $requested ) : null;
		if ( null === $tab ) {
			$tab = $this->registry->get_tab( $default_tab_id );
		}

		echo '<div class="hm-settings__layout">';
		$this->render_nav( $tabs, $tab['id'] );
		echo '<div class="hm-settings__content">';
		$this->render_tab( $tab );
		echo '</div></div></div>';
	}

	/**
	 * A dismissible notice from the `hamista_notice` redirect query arg.
	 */
	private function render_notice(): void {
		$notice = isset( $_GET['hamista_notice'] ) ? sanitize_key( wp_unslash( $_GET['hamista_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
		$map    = [
			'saved'        => [ 'success', __( 'Settings saved.', 'hamista-core' ) ],
			'reset'        => [ 'success', __( 'This tab was reset to its defaults.', 'hamista-core' ) ],
			'imported'     => [ 'success', __( 'Settings imported.', 'hamista-core' ) ],
			'import_error' => [ 'error', __( 'The file could not be imported. It must be a valid Hamista settings export, up to 1 MB.', 'hamista-core' ) ],
			'error'        => [ 'error', __( 'Unknown settings tab.', 'hamista-core' ) ],
		];
		if ( ! isset( $map[ $notice ] ) ) {
			return;
		}
		[ $type, $message ] = $map[ $notice ];
		echo '<div class="hm-alert hm-alert--' . esc_attr( $type ) . '" data-hm-dismiss>' . esc_html( $message ) . '</div>';
	}

	/**
	 * The grouped, searchable tab navigation.
	 *
	 * @param array<int, array> $tabs       Every tab.
	 * @param string            $current_id Current tab id.
	 */
	private function render_nav( array $tabs, string $current_id ): void {
		$groups = [
			'theme'    => __( 'Theme', 'hamista-core' ),
			'platform' => __( 'Platform', 'hamista-core' ),
			'advanced' => __( 'Advanced', 'hamista-core' ),
		];

		echo '<nav class="hm-settings__nav" aria-label="' . esc_attr__( 'Settings tabs', 'hamista-core' ) . '">';
		echo '<div class="hm-settings__search"><input type="search" class="hm-input" data-hm-tab-search placeholder="' . esc_attr__( 'Search settings…', 'hamista-core' ) . '" aria-label="' . esc_attr__( 'Search settings', 'hamista-core' ) . '" /></div>';
		foreach ( $groups as $group_id => $group_label ) {
			$group_tabs = array_values( array_filter( $tabs, static fn( array $t ): bool => $t['group'] === $group_id ) );
			if ( [] === $group_tabs ) {
				continue;
			}
			echo '<div class="hm-settings__nav-group"><h3 class="hm-settings__nav-title">' . esc_html( $group_label ) . '</h3><ul class="hm-settings__nav-list">';
			foreach ( $group_tabs as $t ) {
				$url    = add_query_arg( [ 'page' => Admin_Menu::SETTINGS_SLUG, 'tab' => $t['id'] ], admin_url( 'admin.php' ) );
				$active = ( $t['id'] === $current_id );
				echo '<li><a href="' . esc_url( $url ) . '" class="hm-settings__nav-link' . ( $active ? ' is-active' : '' ) . '" data-hm-tab-label="' . esc_attr( strtolower( (string) $t['title'] ) ) . '"' . ( $active ? ' aria-current="page"' : '' ) . '>';
				if ( '' !== (string) $t['icon'] ) {
					echo hamista_icon( (string) $t['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::get() escapes.
				}
				echo '<span>' . esc_html( (string) $t['title'] ) . '</span></a></li>';
			}
			echo '</ul></div>';
		}
		echo '</nav>';
	}

	/**
	 * The current tab's content.
	 *
	 * @param array $tab Tab.
	 */
	private function render_tab( array $tab ): void {
		if ( 'import-export' === $tab['id'] ) {
			$this->render_import_export( $tab );
			return;
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="hm-settings-form" data-hm-busy data-hm-track-dirty>';
		echo '<input type="hidden" name="action" value="hamista_save_settings" />';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $tab['id'] ) . '" />';
		wp_nonce_field( 'hamista_save_settings_' . $tab['id'], '_hamista_settings_nonce' );

		foreach ( $tab['sections'] ?? [] as $section ) {
			$option = (string) ( $section['option'] ?? $tab['option'] );
			echo '<div class="hm-card hm-settings__section" data-hm-field-group>';
			if ( '' !== (string) ( $section['title'] ?? '' ) ) {
				echo '<h2 class="hm-card__title">' . esc_html( $section['title'] ) . '</h2>';
			}
			if ( '' !== (string) ( $section['description'] ?? '' ) ) {
				echo '<p class="hm-card__text">' . wp_kses_post( $section['description'] ) . '</p>';
			}
			foreach ( $section['fields'] ?? [] as $field ) {
				$this->render_field( $field, $option );
			}
			echo '</div>';
		}

		echo '<div class="hm-settings__savebar" data-hm-savebar>';
		echo '<span class="hm-settings__dirty" data-hm-dirty-indicator hidden>' . esc_html__( 'Unsaved changes', 'hamista-core' ) . '</span>';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Save changes', 'hamista-core' ) . '</button>';
		echo '</div>';
		echo '</form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="hm-settings-reset">';
		echo '<input type="hidden" name="action" value="hamista_reset_settings_tab" />';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $tab['id'] ) . '" />';
		wp_nonce_field( 'hamista_reset_settings_tab_' . $tab['id'], '_hamista_reset_nonce' );
		echo '<button type="submit" class="button hm-settings-reset__btn" data-hm-confirm="' . esc_attr__( 'Reset this tab to its defaults?', 'hamista-core' ) . '">' . esc_html__( 'Reset tab to defaults', 'hamista-core' ) . '</button>';
		echo '</form>';
	}

	/**
	 * One field, wrapped with its label, description and "default" hint.
	 *
	 * @param array  $field  Field definition.
	 * @param string $option Option this field belongs to.
	 */
	private function render_field( array $field, string $option ): void {
		$id      = (string) $field['id'];
		$type    = (string) $field['type'];
		$name    = self::field_name( $id );
		$default = $this->registry->default_for( $option, $id );
		if ( '' !== $option ) {
			$value = Options::get( $option, $id, $default );
		} else {
			$value = $default;
		}
		$show_if_attr = ! empty( $field['show_if'] ) ? Html::attrs( [ 'data-hm-show-if' => (array) $field['show_if'] ] ) : '';
		$bare         = in_array( $type, [ 'html', 'modules' ], true );

		echo '<div class="hm-field hm-field--' . esc_attr( $type ) . '"' . $show_if_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Html::attrs() escapes.
		if ( ! $bare && 'toggle' !== $type && '' !== (string) ( $field['label'] ?? '' ) ) {
			echo '<label class="hm-field__label" for="hm-f-' . esc_attr( preg_replace( '/[^a-zA-Z0-9_-]+/', '-', $name ) ) . '">' . esc_html( $field['label'] ) . '</label>';
		}
		echo '<div class="hm-field__control">';
		if ( 'toggle' === $type && '' !== (string) ( $field['label'] ?? '' ) ) {
			echo '<div class="hm-field__toggle-row">';
			Field_Types::render( $field, $value, $name );
			echo '<span class="hm-field__toggle-label">' . esc_html( $field['label'] ) . '</span></div>';
		} else {
			Field_Types::render( $field, $value, $name );
		}
		echo '</div>';
		if ( '' !== (string) ( $field['description'] ?? '' ) ) {
			echo '<p class="hm-field__help">' . wp_kses_post( $field['description'] ) . '</p>';
		}
		if ( ! $bare && null !== $default && [] !== $default && '' !== $default ) {
			echo '<p class="hm-field__default">' . esc_html( sprintf( /* translators: %s: default value. */ __( 'Default: %s', 'hamista-core' ), self::format_default( $default ) ) ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * The Import / Export tab's content.
	 *
	 * @param array $tab Tab.
	 */
	private function render_import_export( array $tab ): void {
		foreach ( $tab['sections'] ?? [] as $section ) {
			echo '<div class="hm-card hm-settings__section">';
			if ( '' !== (string) ( $section['title'] ?? '' ) ) {
				echo '<h2 class="hm-card__title">' . esc_html( $section['title'] ) . '</h2>';
			}
			foreach ( $section['fields'] ?? [] as $field ) {
				if ( 'html' === $field['type'] ) {
					Field_Types::render( $field, null, '' );
				}
			}
			echo '</div>';
		}

		$summary_key = 'hamista_import_summary_' . get_current_user_id();
		$summary     = get_transient( $summary_key );
		if ( is_array( $summary ) ) {
			delete_transient( $summary_key );
			echo '<div class="hm-alert hm-alert--info">' . esc_html(
				sprintf(
					/* translators: 1: applied values, 2: option count, 3: ignored keys. */
					__( 'Import summary: %1$d value(s) applied across %2$d option(s); %3$d unknown key(s) ignored.', 'hamista-core' ),
					(int) ( $summary['applied'] ?? 0 ),
					(int) ( $summary['options'] ?? 0 ),
					(int) ( $summary['ignored'] ?? 0 )
				)
			) . '</div>';
		}

		echo '<div class="hm-card hm-settings__section">';
		echo '<h2 class="hm-card__title">' . esc_html__( 'Export', 'hamista-core' ) . '</h2>';
		echo '<p class="hm-card__text">' . esc_html__( 'Download every Hamista setting, and module on/off state, as one JSON file.', 'hamista-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="hamista_export_settings" />';
		wp_nonce_field( 'hamista_export_settings' );
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Export settings', 'hamista-core' ) . '</button>';
		echo '</form></div>';

		echo '<div class="hm-card hm-settings__section">';
		echo '<h2 class="hm-card__title">' . esc_html__( 'Import', 'hamista-core' ) . '</h2>';
		echo '<p class="hm-card__text">' . esc_html__( 'Upload a previously exported JSON file (up to 1 MB).', 'hamista-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data" data-hm-busy>';
		echo '<input type="hidden" name="action" value="hamista_import_settings" />';
		wp_nonce_field( 'hamista_import_settings' );
		echo '<input type="file" name="hamista_import_file" accept="application/json" class="hm-input" required="required" />';
		echo '<button type="submit" class="button">' . esc_html__( 'Import settings', 'hamista-core' ) . '</button>';
		echo '</form></div>';
	}

	/* ------------------------------------------------------------ handlers */

	/**
	 * Saves one tab's fields, into their (possibly per-section) options.
	 * `admin-post.php?action=hamista_save_settings`.
	 *
	 * @since 1.0.0
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Hamista settings.', 'hamista-core' ), '', [ 'response' => 403 ] );
		}
		$tab_id = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		check_admin_referer( 'hamista_save_settings_' . $tab_id, '_hamista_settings_nonce' );

		$registry = Plugin::instance()->settings();
		$tab      = $registry->get_tab( $tab_id );
		$notice   = 'saved';

		if ( null === $tab ) {
			$notice = 'error';
		} else {
			$raw     = isset( $_POST['hamista_field'] ) && is_array( $_POST['hamista_field'] ) ? wp_unslash( $_POST['hamista_field'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- unslashed above.
			$updates = self::collect_updates( $tab, $registry, $raw );
			self::apply_updates( $updates );
		}

		self::redirect( $tab_id, $notice );
	}

	/**
	 * Resets one tab's fields to their defaults.
	 * `admin-post.php?action=hamista_reset_settings_tab`.
	 *
	 * @since 1.0.0
	 */
	public function handle_reset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Hamista settings.', 'hamista-core' ), '', [ 'response' => 403 ] );
		}
		$tab_id = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		check_admin_referer( 'hamista_reset_settings_tab_' . $tab_id, '_hamista_reset_nonce' );

		$registry = Plugin::instance()->settings();
		$tab      = $registry->get_tab( $tab_id );
		$notice   = 'reset';

		if ( null === $tab ) {
			$notice = 'error';
		} else {
			$updates = [];
			foreach ( $tab['sections'] ?? [] as $section ) {
				$option = (string) ( $section['option'] ?? $tab['option'] );
				if ( '' === $option ) {
					continue;
				}
				foreach ( $section['fields'] ?? [] as $field ) {
					if ( 'html' === $field['type'] ) {
						continue;
					}
					if ( 'modules' === $field['type'] ) {
						$defaults = [];
						foreach ( Plugin::instance()->modules()->all() as $module_id => $module ) {
							$defaults[ $module_id ] = $module->default_enabled();
						}
						$updates[ $option ] = array_merge( $updates[ $option ] ?? [], $defaults );
						continue;
					}
					$updates[ $option ][ $field['id'] ] = $registry->default_for( $option, $field['id'] );
				}
			}
			self::apply_updates( $updates );
		}

		self::redirect( $tab_id, $notice );
	}

	/**
	 * Downloads every registered option as JSON.
	 * `admin-post.php?action=hamista_export_settings`.
	 *
	 * @since 1.0.0
	 */
	public function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Hamista settings.', 'hamista-core' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'hamista_export_settings' );

		$registry = Plugin::instance()->settings();
		$options  = self::known_options( $registry );

		$payload = [
			'meta'    => [
				'version'     => defined( 'HAMISTA_CORE_VERSION' ) ? HAMISTA_CORE_VERSION : '',
				'exported_at' => gmdate( 'c' ),
			],
			'options' => [],
		];
		foreach ( array_keys( $options ) as $option ) {
			$value                       = get_option( $option, [] );
			$payload['options'][ $option ] = is_array( $value ) ? $value : [];
		}

		$site     = sanitize_title( get_bloginfo( 'name' ) );
		$filename = sprintf( 'hamista-settings-%s-%s.json', '' !== $site ? $site : 'site', gmdate( 'Y-m-d' ) );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download.
		exit;
	}

	/**
	 * Imports a previously exported JSON file.
	 * `admin-post.php?action=hamista_import_settings`.
	 *
	 * @since 1.0.0
	 */
	public function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Hamista settings.', 'hamista-core' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'hamista_import_settings' );

		$notice = 'import_error';
		$file   = $_FILES['hamista_import_file'] ?? null;

		if (
			is_array( $file )
			&& UPLOAD_ERR_OK === (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE )
			&& is_uploaded_file( (string) ( $file['tmp_name'] ?? '' ) )
			&& (int) ( $file['size'] ?? 0 ) > 0
			&& (int) $file['size'] <= MB_IN_BYTES
		) {
			$raw  = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- uploaded temp file, already size-checked.
			$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

			if ( is_array( $data ) && isset( $data['options'] ) && is_array( $data['options'] ) ) {
				$registry       = Plugin::instance()->settings();
				$known_options  = self::known_options( $registry );
				$applied        = 0;
				$ignored        = 0;
				$updated_count  = 0;

				foreach ( $data['options'] as $option => $values ) {
					$option = sanitize_key( (string) $option );
					if ( ! isset( $known_options[ $option ] ) || ! is_array( $values ) ) {
						continue;
					}
					$current = get_option( $option, [] );
					$current = is_array( $current ) ? $current : [];

					if ( 'hamista_modules' === $option ) {
						foreach ( $values as $id => $value ) {
							$id = sanitize_key( (string) $id );
							if ( '' === $id ) {
								++$ignored;
								continue;
							}
							$current[ $id ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
							++$applied;
						}
					} else {
						$fields = [];
						foreach ( $registry->fields_for_option( $option ) as $entry ) {
							$fields[ $entry['field']['id'] ] = $entry['field'];
						}
						foreach ( $values as $id => $value ) {
							$id = sanitize_key( (string) $id );
							if ( ! isset( $fields[ $id ] ) || 'html' === $fields[ $id ]['type'] ) {
								++$ignored;
								continue;
							}
							$field = $fields[ $id ];
							if ( ! array_key_exists( 'default', $field ) ) {
								$field['default'] = $registry->default_for( $option, $id );
							}
							$current[ $id ] = Field_Types::sanitize( $field, $value );
							++$applied;
						}
					}

					update_option( $option, $current );
					++$updated_count;
				}

				set_transient(
					'hamista_import_summary_' . get_current_user_id(),
					[ 'applied' => $applied, 'ignored' => $ignored, 'options' => $updated_count ],
					60
				);
				$notice = 'imported';
			}
		}

		$redirect = add_query_arg( [ 'page' => Admin_Menu::SETTINGS_SLUG, 'tab' => 'import-export', 'hamista_notice' => $notice ], admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/* --------------------------------------------------------------- utils */

	/**
	 * The `hamista_field[…]` name attribute for a field id.
	 *
	 * @param string $id Field id.
	 * @return string
	 */
	private static function field_name( string $id ): string {
		return 'hamista_field[' . $id . ']';
	}

	/**
	 * Every option name any registered tab or section uses, plus `hamista_modules`.
	 *
	 * @param Settings_Registry $registry Registry.
	 * @return array<string, true>
	 */
	private static function known_options( Settings_Registry $registry ): array {
		$options = [ 'hamista_modules' => true ];
		foreach ( $registry->tabs() as $tab ) {
			if ( '' !== $tab['option'] ) {
				$options[ $tab['option'] ] = true;
			}
			foreach ( $tab['sections'] ?? [] as $section ) {
				$option = (string) ( $section['option'] ?? '' );
				if ( '' !== $option ) {
					$options[ $option ] = true;
				}
			}
		}
		return $options;
	}

	/**
	 * Sanitizes every field of a tab against submitted raw values, grouped by option.
	 *
	 * @param array              $tab      Tab.
	 * @param Settings_Registry  $registry Registry.
	 * @param array              $raw      Submitted `hamista_field` values (unslashed).
	 * @return array<string, array<string, mixed>>
	 */
	private static function collect_updates( array $tab, Settings_Registry $registry, array $raw ): array {
		$updates = [];
		foreach ( $tab['sections'] ?? [] as $section ) {
			$option = (string) ( $section['option'] ?? $tab['option'] );
			if ( '' === $option ) {
				continue;
			}
			foreach ( $section['fields'] ?? [] as $field ) {
				if ( 'html' === $field['type'] ) {
					continue;
				}
				$id = $field['id'];
				if ( 'modules' === $field['type'] ) {
					// The `modules` field represents the whole flat `hamista_modules`
					// option (id => bool), not one key nested under the field id.
					$sanitized              = Field_Types::sanitize( $field, $raw[ $id ] ?? null );
					$updates[ $option ]     = array_merge( $updates[ $option ] ?? [], is_array( $sanitized ) ? $sanitized : [] );
					continue;
				}
				if ( ! array_key_exists( 'default', $field ) ) {
					$field['default'] = $registry->default_for( $option, $id );
				}
				$updates[ $option ][ $id ] = Field_Types::sanitize( $field, $raw[ $id ] ?? null );
			}
		}
		return $updates;
	}

	/**
	 * Merges sanitized updates into their options.
	 *
	 * @param array<string, array<string, mixed>> $updates option => [ key => value ].
	 */
	private static function apply_updates( array $updates ): void {
		foreach ( $updates as $option => $values ) {
			$current = get_option( $option, [] );
			$current = is_array( $current ) ? $current : [];
			update_option( $option, array_merge( $current, $values ) );
		}
	}

	/**
	 * Redirects back to the settings page with a notice.
	 *
	 * @param string $tab_id Tab id.
	 * @param string $notice Notice key.
	 */
	private static function redirect( string $tab_id, string $notice ): void {
		$redirect = add_query_arg( [ 'page' => Admin_Menu::SETTINGS_SLUG, 'tab' => $tab_id, 'hamista_notice' => $notice ], admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * A human-readable default value for the "default" hint.
	 *
	 * @param mixed $default Default value.
	 * @return string
	 */
	private static function format_default( $default ): string {
		if ( is_bool( $default ) ) {
			return $default ? __( 'On', 'hamista-core' ) : __( 'Off', 'hamista-core' );
		}
		if ( is_array( $default ) ) {
			return implode( ', ', array_map( 'strval', $default ) );
		}
		return (string) $default;
	}
}

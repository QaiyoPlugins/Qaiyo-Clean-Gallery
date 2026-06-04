<?php
/**
 * Qaiyo Clean Gallery – Settings / About page (Qaiyo brand admin felület).
 *
 * - Settings API: qcg_settings option (oszlopváltó ikon színek).
 * - Frontend: a választott színek CSS custom property-ként kerülnek be wp_head-en.
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Settings {

	const PAGE_SLUG   = 'qcg-info';
	const OPTION_NAME = 'qcg_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_frontend_css_vars' ), 5 );
	}

	/**
	 * Default option values.
	 */
	public static function defaults() {
		return array(
			'col_icon_color'        => '#888888',
			'col_icon_hover_color'  => '#6c5ce7',
			'col_icon_active_color' => '#6c5ce7',
			'deep_linking'          => true,
			'social_share'          => true,
			'right_click_protect'   => false,
		);
	}

	public static function get( $key = null ) {
		$opts = wp_parse_args(
			get_option( self::OPTION_NAME, array() ),
			self::defaults()
		);
		if ( null === $key ) {
			return $opts;
		}
		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	public static function register_settings() {
		register_setting(
			'qcg_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_option' ),
				'default'           => self::defaults(),
			)
		);
	}

	/** Booleans in the settings array. */
	private static $boolean_keys = array( 'deep_linking', 'social_share', 'right_click_protect' );

	public static function sanitize_option( $input ) {
		$out      = array();
		$defaults = self::defaults();
		foreach ( $defaults as $key => $default ) {
			if ( in_array( $key, self::$boolean_keys, true ) ) {
				$out[ $key ] = ! empty( $input[ $key ] );
				continue;
			}
			$raw = isset( $input[ $key ] ) ? (string) $input[ $key ] : $default;
			$col = sanitize_hex_color( $raw );
			$out[ $key ] = $col ? $col : $default;
		}
		return $out;
	}

	public static function add_submenu() {
		add_submenu_page(
			'edit.php?post_type=' . Qcg_CPT::POST_TYPE,
			__( 'Qaiyo Clean Gallery', 'qaiyo-clean-gallery' ),
			__( 'Info & Help', 'qaiyo-clean-gallery' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue( $hook ) {
		if ( strpos( (string) $hook, self::PAGE_SLUG ) === false ) {
			return;
		}
		wp_enqueue_style( 'qcg-admin', QCG_URL . 'assets/css/admin.css', array(), QCG_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'qcg-settings',
			QCG_URL . 'assets/js/settings.js',
			array( 'jquery', 'wp-color-picker' ),
			QCG_VERSION,
			true
		);
	}

	/**
	 * Inject CSS custom properties on the frontend so the column-switcher
	 * icons follow the admin-selected colors.
	 */
	public static function print_frontend_css_vars() {
		$o = self::get();
		printf(
			'<style id="qcg-col-vars">:root{--qcg-col-icon:%s;--qcg-col-icon-hover:%s;--qcg-col-icon-active:%s;}</style>',
			esc_html( $o['col_icon_color'] ),
			esc_html( $o['col_icon_hover_color'] ),
			esc_html( $o['col_icon_active_color'] )
		);
		// Expose boolean settings as a tiny JSON script tag for gallery.js.
		$js_opts = array(
			'deepLinking'      => ! empty( $o['deep_linking'] ),
			'socialShare'      => ! empty( $o['social_share'] ),
			'rightClickProtect' => ! empty( $o['right_click_protect'] ),
		);
		printf(
			'<script id="qcg-settings" type="application/json">%s</script>',
			wp_json_encode( $js_opts )
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hf_active = Qcg_HappyFiles::is_available();
		$opts      = self::get();
		?>
		<div class="wrap qcg-wrap">
			<h1 class="qcg-page-title">
				<span class="qcg-brand-word">Qaiyo</span> Clean Gallery
				<span class="qcg-version">v<?php echo esc_html( QCG_VERSION ); ?></span>
			</h1>

			<p class="qcg-page-desc">
				<?php esc_html_e( 'Lightweight, professional gallery plugin — Grid, Masonry, Lightbox, filtering and lazy loading. No bloat, no dependencies.', 'qaiyo-clean-gallery' ); ?>
			</p>

			<?php settings_errors( self::OPTION_NAME ); ?>

			<div class="qcg-card">
				<div class="qcg-card-header">
					<h3><?php esc_html_e( 'Column switcher icon colors', 'qaiyo-clean-gallery' ); ?></h3>
				</div>
				<div class="qcg-card-body">
					<p class="qcg-card-hint">
						<?php esc_html_e( 'These colors are used for the 3-/4-column switcher icons on the [clean_gallery_all] view.', 'qaiyo-clean-gallery' ); ?>
					</p>
					<form method="post" action="options.php" class="qcg-form">
						<?php settings_fields( 'qcg_settings_group' ); ?>
						<table class="form-table qcg-color-table" role="presentation">
							<tr>
								<th scope="row"><label for="qcg-col-icon-color"><?php esc_html_e( 'Default color', 'qaiyo-clean-gallery' ); ?></label></th>
								<td>
									<input type="text" id="qcg-col-icon-color" class="qcg-color-field"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[col_icon_color]"
										value="<?php echo esc_attr( $opts['col_icon_color'] ); ?>"
										data-default-color="<?php echo esc_attr( self::defaults()['col_icon_color'] ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="qcg-col-icon-hover-color"><?php esc_html_e( 'Hover color', 'qaiyo-clean-gallery' ); ?></label></th>
								<td>
									<input type="text" id="qcg-col-icon-hover-color" class="qcg-color-field"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[col_icon_hover_color]"
										value="<?php echo esc_attr( $opts['col_icon_hover_color'] ); ?>"
										data-default-color="<?php echo esc_attr( self::defaults()['col_icon_hover_color'] ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="qcg-col-icon-active-color"><?php esc_html_e( 'Active color', 'qaiyo-clean-gallery' ); ?></label></th>
								<td>
									<input type="text" id="qcg-col-icon-active-color" class="qcg-color-field"
										name="<?php echo esc_attr( self::OPTION_NAME ); ?>[col_icon_active_color]"
										value="<?php echo esc_attr( $opts['col_icon_active_color'] ); ?>"
										data-default-color="<?php echo esc_attr( self::defaults()['col_icon_active_color'] ); ?>">
								</td>
							</tr>
						</table>

						<div class="qcg-preview-wrap">
							<span class="qcg-preview-label"><?php esc_html_e( 'Preview', 'qaiyo-clean-gallery' ); ?>:</span>
							<div class="qcg-col-switcher qcg-preview" role="group" aria-label="<?php esc_attr_e( 'Columns preview', 'qaiyo-clean-gallery' ); ?>"
								 style="--qcg-col-icon:<?php echo esc_attr( $opts['col_icon_color'] ); ?>;--qcg-col-icon-hover:<?php echo esc_attr( $opts['col_icon_hover_color'] ); ?>;--qcg-col-icon-active:<?php echo esc_attr( $opts['col_icon_active_color'] ); ?>;">
								<button type="button" class="qcg-col-btn is-active" data-cols="3">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" fill="currentColor" aria-hidden="true">
										<rect x="20.07" y="156.85" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
										<rect x="20.07" y="430.39" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
										<rect x="293.62" y="156.85" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
										<rect x="293.62" y="430.39" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
										<rect x="567.17" y="156.85" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
										<rect x="567.17" y="430.39" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
									</svg>
								</button>
								<button type="button" class="qcg-col-btn" data-cols="4">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" fill="currentColor" aria-hidden="true">
										<rect x="20.07" y="221.21" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="20.07" y="422.35" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="221.21" y="221.21" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="221.21" y="422.35" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="422.35" y="221.21" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="422.35" y="422.35" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="623.49" y="221.21" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
										<rect x="623.49" y="422.35" width="156.44" height="156.44" rx="22.35" ry="22.35"/>
									</svg>
								</button>
							</div>
						</div>

						<h4 class="qcg-section-title"><?php esc_html_e( 'Lightbox & Protection', 'qaiyo-clean-gallery' ); ?></h4>

						<table class="form-table qcg-toggle-table" role="presentation">
							<tr>
								<th scope="row"><label for="qcg-deep-linking"><?php esc_html_e( 'Deep linking', 'qaiyo-clean-gallery' ); ?></label></th>
								<td>
									<label class="qcg-toggle">
										<input type="checkbox" id="qcg-deep-linking"
											name="<?php echo esc_attr( self::OPTION_NAME ); ?>[deep_linking]" value="1"
											<?php checked( $opts['deep_linking'] ); ?>>
										<span class="qcg-toggle-desc"><?php esc_html_e( 'Add #hash to the URL when navigating the lightbox, so individual images can be linked and shared directly.', 'qaiyo-clean-gallery' ); ?></span>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="qcg-social-share"><?php esc_html_e( 'Social share buttons', 'qaiyo-clean-gallery' ); ?></label></th>
								<td>
									<label class="qcg-toggle">
										<input type="checkbox" id="qcg-social-share"
											name="<?php echo esc_attr( self::OPTION_NAME ); ?>[social_share]" value="1"
											<?php checked( $opts['social_share'] ); ?>>
										<span class="qcg-toggle-desc"><?php esc_html_e( 'Show Facebook, X, Pinterest and copy-link buttons inside the lightbox.', 'qaiyo-clean-gallery' ); ?></span>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="qcg-right-click"><?php esc_html_e( 'Right-click protection', 'qaiyo-clean-gallery' ); ?></label></th>
								<td>
									<label class="qcg-toggle">
										<input type="checkbox" id="qcg-right-click"
											name="<?php echo esc_attr( self::OPTION_NAME ); ?>[right_click_protect]" value="1"
											<?php checked( $opts['right_click_protect'] ); ?>>
										<span class="qcg-toggle-desc"><?php esc_html_e( 'Disable right-click and drag on gallery images. Prevents casual downloading (not bulletproof).', 'qaiyo-clean-gallery' ); ?></span>
									</label>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Save changes', 'qaiyo-clean-gallery' ) ); ?>
					</form>
				</div>
			</div>

			<div class="qcg-card">
				<div class="qcg-card-header">
					<h3><?php esc_html_e( 'Getting started', 'qaiyo-clean-gallery' ); ?></h3>
				</div>
				<div class="qcg-card-body">
					<ol>
						<li><?php
							echo wp_kses(
								sprintf(
									/* translators: %s: link to gallery list */
									__( 'Create a new gallery under <a href="%s">Clean Gallery → Add New</a>.', 'qaiyo-clean-gallery' ),
									esc_url( admin_url( 'post-new.php?post_type=' . Qcg_CPT::POST_TYPE ) )
								),
								array( 'a' => array( 'href' => array() ) )
							);
						?></li>
						<li><?php esc_html_e( 'Pick images from the Media Library, set layout and columns, then publish.', 'qaiyo-clean-gallery' ); ?></li>
						<li><?php
							echo wp_kses(
								__( 'Embed it with the <code>[clean_gallery id="X"]</code> shortcode or the <strong>Qaiyo Clean Gallery</strong> Gutenberg block.', 'qaiyo-clean-gallery' ),
								array( 'code' => array(), 'strong' => array() )
							);
						?></li>
					</ol>
				</div>
			</div>

			<div class="qcg-card">
				<div class="qcg-card-header">
					<h3><?php esc_html_e( 'Shortcodes', 'qaiyo-clean-gallery' ); ?></h3>
				</div>
				<div class="qcg-card-body">
					<p><code>[clean_gallery id="42"]</code> — <?php esc_html_e( 'single gallery view', 'qaiyo-clean-gallery' ); ?></p>
					<p><code>[clean_gallery_all card_columns="3" filter="true"]</code> — <?php esc_html_e( 'all galleries grid with paging, sorting and category filter', 'qaiyo-clean-gallery' ); ?></p>
				</div>
			</div>

			<div class="qcg-card">
				<div class="qcg-card-header">
					<h3>
						<?php esc_html_e( 'HappyFiles integration', 'qaiyo-clean-gallery' ); ?>
						<?php if ( $hf_active ) : ?>
							<span class="qcg-badge qcg-badge-active"><?php esc_html_e( 'ACTIVE', 'qaiyo-clean-gallery' ); ?></span>
						<?php else : ?>
							<span class="qcg-badge qcg-badge-inactive"><?php esc_html_e( 'NOT DETECTED', 'qaiyo-clean-gallery' ); ?></span>
						<?php endif; ?>
					</h3>
				</div>
				<div class="qcg-card-body">
					<?php if ( $hf_active ) : ?>
						<p><?php esc_html_e( 'HappyFiles is active. On the gallery editor screen you can pick a HappyFiles folder and bulk-import all its images.', 'qaiyo-clean-gallery' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Install HappyFiles (free or Pro) to enable bulk-importing entire media folders into a gallery.', 'qaiyo-clean-gallery' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<footer class="qcg-footer">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: plugin version */
						__( 'Qaiyo Clean Gallery v%s — by Qaiyo by PixelDesigns · qaiyo-plugins.com', 'qaiyo-clean-gallery' ),
						QCG_VERSION
					)
				);
				?>
			</footer>
		</div>
		<?php
	}
}

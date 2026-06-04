<?php
/**
 * Qaiyo Clean Gallery – Meta boxes (gallery images + settings + shortcode).
 *
 * Meta keys:
 *   _clean_gallery_images    (array) manual image ID list
 *   _clean_gallery_settings  (array) layout / columns / lightbox / …
 *   _qcg_hf_folder           (int)   HappyFiles taxonomy term_id (live link); 0 = manual mode
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Meta_Boxes {

	const META_IMAGES    = '_clean_gallery_images';
	const META_SETTINGS  = '_clean_gallery_settings';
	const META_HF_FOLDER = '_qcg_hf_folder';

	public static function init() {
		// priority 5 → mi futunk a SEO plugin-ek (általában 10) ELŐTT
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ), 5 );
		add_action( 'save_post_' . Qcg_CPT::POST_TYPE, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );

		// Force a Galéria képek doboz a normal kontextusban legelölre — felülírja a SEO doboz sorrendjét.
		add_filter( 'get_user_option_meta-box-order_' . Qcg_CPT::POST_TYPE, array( __CLASS__, 'force_meta_box_order' ) );
	}

	/**
	 * Mindig a Galéria képek doboz legyen az első a normal kontextusban.
	 */
	public static function force_meta_box_order( $order ) {
		if ( ! is_array( $order ) ) {
			$order = array();
		}

		$normal = isset( $order['normal'] ) ? (string) $order['normal'] : '';
		$boxes  = $normal ? explode( ',', $normal ) : array();
		$boxes  = array_values( array_filter( array_diff( $boxes, array( 'qcg_gallery_images' ) ) ) );
		array_unshift( $boxes, 'qcg_gallery_images' );
		$order['normal'] = implode( ',', $boxes );

		return $order;
	}

	public static function add() {
		add_meta_box(
			'qcg_gallery_images',
			__( 'Gallery Images', 'qaiyo-clean-gallery' ),
			array( __CLASS__, 'render_images_box' ),
			Qcg_CPT::POST_TYPE,
			'normal',
			'high'
		);

		// Shortcode box — közvetlenül a Közzététel doboz alá (side, default priority).
		add_meta_box(
			'qcg_shortcode',
			__( 'Shortcode', 'qaiyo-clean-gallery' ),
			array( __CLASS__, 'render_shortcode_box' ),
			Qcg_CPT::POST_TYPE,
			'side',
			'high'
		);

		add_meta_box(
			'qcg_gallery_settings',
			__( 'Gallery Settings', 'qaiyo-clean-gallery' ),
			array( __CLASS__, 'render_settings_box' ),
			Qcg_CPT::POST_TYPE,
			'side',
			'default'
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		global $post;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( ! isset( $post ) || Qcg_CPT::POST_TYPE !== $post->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style(
			'qcg-admin',
			QCG_URL . 'assets/css/admin.css',
			array(),
			QCG_VERSION
		);
		wp_enqueue_script(
			'qcg-admin',
			QCG_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'media-upload' ),
			QCG_VERSION,
			true
		);
		wp_localize_script(
			'qcg-admin',
			'qcgAdmin',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'qcg_admin' ),
				'thresholdBytes' => Qcg_Image_Optimizer::THRESHOLD_BYTES,
				'hasHappyFiles'  => Qcg_HappyFiles::is_available(),
				'i18n'           => array(
					'title'        => esc_html__( 'Select Gallery Images', 'qaiyo-clean-gallery' ),
					'button'       => esc_html__( 'Add to Gallery', 'qaiyo-clean-gallery' ),
					'remove'       => esc_html__( 'Remove', 'qaiyo-clean-gallery' ),
					'confirmClear' => esc_html__( 'Remove all images from this gallery?', 'qaiyo-clean-gallery' ),
					'placeholder'  => esc_html__( 'No images yet. Click "Add / Insert Images" to get started.', 'qaiyo-clean-gallery' ),
					/* translators: 1: filename, 2: filesize */
					'warnSingle'   => esc_html__( 'Large image: "%1$s" (%2$s). Qaiyo Clean Gallery will automatically compress it to under 1 MB after upload.', 'qaiyo-clean-gallery' ),
					/* translators: %d: number of large images */
					'warnMultiple' => esc_html__( '%d large image(s) detected (over 1.5 MB each). Qaiyo Clean Gallery will automatically compress them after upload.', 'qaiyo-clean-gallery' ),
					'copied'       => esc_html__( 'Copied!', 'qaiyo-clean-gallery' ),
					'copy'         => esc_html__( 'Copy', 'qaiyo-clean-gallery' ),
				),
			)
		);
	}

	// =========================================================================
	// Images meta box
	// =========================================================================
	public static function render_images_box( $post ) {
		$ids       = get_post_meta( $post->ID, self::META_IMAGES, true );
		$ids       = is_array( $ids ) ? $ids : array();
		$hf_folder = (int) get_post_meta( $post->ID, self::META_HF_FOLDER, true );
		$hf_active = Qcg_HappyFiles::is_available();

		$linked_folder = null;
		$linked_count  = 0;
		if ( $hf_folder && $hf_active ) {
			$term = get_term( $hf_folder, Qcg_HappyFiles::taxonomy() );
			if ( $term && ! is_wp_error( $term ) ) {
				$linked_folder = $term;
				$linked_count  = count( Qcg_HappyFiles::get_folder_image_ids( $hf_folder ) );
			}
		}

		wp_nonce_field( 'qcg_meta_save', 'qcg_meta_nonce' );
		?>
		<div id="qcg-image-manager" class="<?php echo $linked_folder ? 'qcg-hf-linked' : ''; ?>">

			<?php if ( $hf_active ) : ?>
				<div class="qcg-hf-source">
					<label for="qcg-hf-folder" class="qcg-hf-label">
						<span class="dashicons dashicons-portfolio"></span>
						<?php esc_html_e( 'HappyFiles folder (live link)', 'qaiyo-clean-gallery' ); ?>
					</label>
					<select name="qcg_hf_folder" id="qcg-hf-folder" class="qcg-hf-select">
						<option value="0"><?php esc_html_e( '— None (manual gallery) —', 'qaiyo-clean-gallery' ); ?></option>
						<?php foreach ( Qcg_HappyFiles::get_folders() as $folder ) : ?>
							<option value="<?php echo esc_attr( $folder['id'] ); ?>" <?php selected( $hf_folder, $folder['id'] ); ?>>
								<?php echo esc_html( str_repeat( '— ', max( 0, (int) $folder['depth'] ) ) . $folder['name'] . ' (' . $folder['count'] . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="qcg-hf-hint">
						<?php esc_html_e( 'Select a folder to live-link this gallery. Images stay in sync with HappyFiles automatically — no import needed.', 'qaiyo-clean-gallery' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $linked_folder ) : ?>
				<div class="qcg-hf-banner">
					<span class="dashicons dashicons-update"></span>
					<?php
					printf(
						/* translators: 1: folder name, 2: number of images */
						esc_html__( 'Live-linked to HappyFiles folder %1$s (%2$d image(s)). Add or remove images directly in HappyFiles — this gallery updates automatically.', 'qaiyo-clean-gallery' ),
						'<strong>' . esc_html( $linked_folder->name ) . '</strong>',
						(int) $linked_count
					);
					?>
				</div>

				<?php
				$preview_ids = Qcg_HappyFiles::get_folder_image_ids( $hf_folder );
				if ( ! empty( $preview_ids ) ) :
				?>
					<div class="qcg-sortable-list qcg-hf-preview">
						<?php foreach ( array_slice( $preview_ids, 0, 24 ) as $pid ) : ?>
							<?php self::render_thumb( (int) $pid, true ); ?>
						<?php endforeach; ?>
						<?php if ( count( $preview_ids ) > 24 ) : ?>
							<div class="qcg-thumb-item qcg-thumb-more">
								<span>+<?php echo (int) ( count( $preview_ids ) - 24 ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			<?php else : ?>

				<div id="qcg-sortable" class="qcg-sortable-list"
					 data-placeholder="<?php esc_attr_e( 'No images yet. Click "Add / Insert Images" to get started.', 'qaiyo-clean-gallery' ); ?>">
					<?php foreach ( $ids as $id ) : ?>
						<?php self::render_thumb( (int) $id ); ?>
					<?php endforeach; ?>
				</div>

				<p class="qcg-actions">
					<button type="button" id="qcg-add-images" class="button button-primary">
						<?php esc_html_e( 'Add / Insert Images', 'qaiyo-clean-gallery' ); ?>
					</button>
					<button type="button" id="qcg-clear-images" class="button button-secondary">
						<?php esc_html_e( 'Remove All', 'qaiyo-clean-gallery' ); ?>
					</button>
				</p>

				<input type="hidden" name="qcg_image_ids" id="qcg-image-ids"
					   value="<?php echo esc_attr( implode( ',', array_map( 'absint', $ids ) ) ); ?>">

			<?php endif; ?>

		</div>
		<?php
	}

	private static function render_thumb( $id, $readonly = false ) {
		$src = wp_get_attachment_image_src( $id, 'thumbnail' );
		if ( ! $src ) {
			return;
		}
		?>
		<div class="qcg-thumb-item <?php echo $readonly ? 'is-readonly' : ''; ?>" data-id="<?php echo esc_attr( $id ); ?>">
			<img src="<?php echo esc_url( $src[0] ); ?>" alt="">
			<?php if ( ! $readonly ) : ?>
				<span class="qcg-remove-thumb dashicons dashicons-no-alt"
					  title="<?php esc_attr_e( 'Remove', 'qaiyo-clean-gallery' ); ?>"></span>
				<span class="qcg-drag-handle dashicons dashicons-move"></span>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Shortcode box (side)
	// =========================================================================
	public static function render_shortcode_box( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			?>
			<p class="qcg-shortcode-empty">
				<?php esc_html_e( 'Save the gallery first to see its shortcode.', 'qaiyo-clean-gallery' ); ?>
			</p>
			<?php
			return;
		}

		$shortcode = '[clean_gallery id="' . (int) $post->ID . '"]';
		$block     = '<!-- wp:qaiyo-clean-gallery/gallery {"galleryId":' . (int) $post->ID . '} /-->';
		?>
		<p class="qcg-shortcode-label"><?php esc_html_e( 'Shortcode', 'qaiyo-clean-gallery' ); ?></p>
		<div class="qcg-shortcode-wrap">
			<input type="text" readonly class="qcg-shortcode-input"
				   value="<?php echo esc_attr( $shortcode ); ?>"
				   onclick="this.select();">
			<button type="button" class="button qcg-copy-btn" data-clipboard-target=".qcg-shortcode-input">
				<span class="dashicons dashicons-admin-page"></span>
			</button>
		</div>

		<p class="qcg-shortcode-label"><?php esc_html_e( 'All galleries (paged)', 'qaiyo-clean-gallery' ); ?></p>
		<div class="qcg-shortcode-wrap">
			<input type="text" readonly class="qcg-shortcode-input"
				   value="[clean_gallery_all]"
				   onclick="this.select();">
		</div>

		<p class="qcg-shortcode-hint"><?php esc_html_e( 'Paste the shortcode into any post, page or widget to embed this gallery.', 'qaiyo-clean-gallery' ); ?></p>
		<?php
	}

	// =========================================================================
	// Settings box
	// =========================================================================
	public static function render_settings_box( $post ) {
		$settings = get_post_meta( $post->ID, self::META_SETTINGS, true );
		$defaults = self::default_settings();
		$s        = wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
		?>
		<table class="form-table qcg-settings-table">
			<tr>
				<th><label for="qcg_layout"><?php esc_html_e( 'Layout', 'qaiyo-clean-gallery' ); ?></label></th>
				<td>
					<select name="qcg_settings[layout]" id="qcg_layout">
						<?php foreach ( self::layout_choices() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['layout'], $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="qcg_columns"><?php esc_html_e( 'Columns', 'qaiyo-clean-gallery' ); ?></label></th>
				<td>
					<select name="qcg_settings[columns]" id="qcg_columns">
						<?php foreach ( array( 2, 3, 4, 5 ) as $c ) : ?>
							<option value="<?php echo esc_attr( $c ); ?>" <?php selected( (int) $s['columns'], $c ); ?>><?php echo esc_html( $c ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="qcg_lightbox"><?php esc_html_e( 'Lightbox', 'qaiyo-clean-gallery' ); ?></label></th>
				<td>
					<input type="checkbox" name="qcg_settings[lightbox]" id="qcg_lightbox" value="1"
						<?php checked( (bool) $s['lightbox'] ); ?>>
				</td>
			</tr>
			<tr>
				<th><label for="qcg_captions"><?php esc_html_e( 'Captions', 'qaiyo-clean-gallery' ); ?></label></th>
				<td>
					<input type="checkbox" name="qcg_settings[captions]" id="qcg_captions" value="1"
						<?php checked( (bool) $s['captions'] ); ?>>
				</td>
			</tr>
			<tr>
				<th><label for="qcg_lazy"><?php esc_html_e( 'Lazy loading', 'qaiyo-clean-gallery' ); ?></label></th>
				<td>
					<input type="checkbox" name="qcg_settings[lazy]" id="qcg_lazy" value="1"
						<?php checked( (bool) $s['lazy'] ); ?>>
				</td>
			</tr>
			<tr>
				<th><label for="qcg_gap"><?php esc_html_e( 'Gap (px)', 'qaiyo-clean-gallery' ); ?></label></th>
				<td>
					<input type="number" name="qcg_settings[gap]" id="qcg_gap"
						   value="<?php echo esc_attr( (int) $s['gap'] ); ?>"
						   min="0" max="60" step="2" style="width:70px">
				</td>
			</tr>
		</table>
		<?php
	}

	// =========================================================================
	// Save
	// =========================================================================
	public static function save( $post_id ) {
		if ( ! isset( $_POST['qcg_meta_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['qcg_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'qcg_meta_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// HappyFiles folder (live link). Mentjük akkor is, ha nincs HF aktív — nem árt.
		$hf_folder = isset( $_POST['qcg_hf_folder'] ) ? absint( wp_unslash( $_POST['qcg_hf_folder'] ) ) : 0;
		if ( $hf_folder > 0 ) {
			update_post_meta( $post_id, self::META_HF_FOLDER, $hf_folder );
		} else {
			delete_post_meta( $post_id, self::META_HF_FOLDER );
		}

		// Manual image IDs csak akkor jönnek, ha NEM HF módban van a doboz.
		if ( 0 === $hf_folder && isset( $_POST['qcg_image_ids'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['qcg_image_ids'] ) );
			$ids = array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );
			update_post_meta( $post_id, self::META_IMAGES, $ids );
		}

		// Settings
		if ( isset( $_POST['qcg_settings'] ) && is_array( $_POST['qcg_settings'] ) ) {
			$raw   = wp_unslash( $_POST['qcg_settings'] );
			$clean = self::sanitize_settings( $raw );
			update_post_meta( $post_id, self::META_SETTINGS, $clean );
		}
	}

	public static function sanitize_settings( $raw ) {
		$defaults = self::default_settings();
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		$layout = isset( $raw['layout'] ) ? sanitize_key( $raw['layout'] ) : $defaults['layout'];
		if ( ! in_array( $layout, self::allowed_layouts(), true ) ) {
			$layout = $defaults['layout'];
		}

		return array(
			'layout'   => $layout,
			'columns'  => min( 5, max( 2, isset( $raw['columns'] ) ? (int) $raw['columns'] : (int) $defaults['columns'] ) ),
			'lightbox' => ! empty( $raw['lightbox'] ),
			'captions' => ! empty( $raw['captions'] ),
			'lazy'     => ! empty( $raw['lazy'] ),
			'gap'      => min( 60, max( 0, isset( $raw['gap'] ) ? (int) $raw['gap'] : (int) $defaults['gap'] ) ),
		);
	}

	/**
	 * Az összes támogatott layout slug listája.
	 *
	 * @return string[]
	 */
	public static function allowed_layouts() {
		return array( 'grid', 'masonry', 'standard', 'justified', 'bento' );
	}

	/**
	 * Layout dropdown opciók (slug => fordított címke).
	 *
	 * @return array
	 */
	public static function layout_choices() {
		return array(
			'grid'      => __( 'Grid', 'qaiyo-clean-gallery' ),
			'masonry'   => __( 'Masonry', 'qaiyo-clean-gallery' ),
			'standard'  => __( 'Standard (uniform squares)', 'qaiyo-clean-gallery' ),
			'justified' => __( 'Justified (equal-height rows)', 'qaiyo-clean-gallery' ),
			'bento'     => __( 'Bento (asymmetric)', 'qaiyo-clean-gallery' ),
		);
	}

	public static function default_settings() {
		return array(
			'layout'   => 'grid',
			'columns'  => 3,
			'lightbox' => true,
			'captions' => true,
			'lazy'     => true,
			'gap'      => 12,
		);
	}

	public static function get_settings( $post_id ) {
		$stored = get_post_meta( $post_id, self::META_SETTINGS, true );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::default_settings() );
	}

	/**
	 * Kép ID-k a galériához.
	 *
	 * Ha HappyFiles folder-rel élő-linkelve van, onnan jönnek élőben.
	 * Egyébként a manuálisan beállított META_IMAGES tömb.
	 */
	public static function get_image_ids( $post_id ) {
		$hf_folder = (int) get_post_meta( $post_id, self::META_HF_FOLDER, true );
		if ( $hf_folder > 0 && Qcg_HappyFiles::is_available() ) {
			return Qcg_HappyFiles::get_folder_image_ids( $hf_folder );
		}

		$ids = get_post_meta( $post_id, self::META_IMAGES, true );
		return is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();
	}

	public static function get_linked_folder_id( $post_id ) {
		return (int) get_post_meta( $post_id, self::META_HF_FOLDER, true );
	}
}

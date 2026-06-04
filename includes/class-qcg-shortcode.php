<?php
/**
 * Qaiyo Clean Gallery – Shortcodes
 *
 * [clean_gallery id="42"]      – egy galéria képei + lightbox
 * [clean_gallery_all …]        – kártyás lista, lapozás, szűrő, rendezés, oszlopváltó
 *
 * SEO: hozzáadunk schema.org/ImageGallery + ItemList markupot.
 * AI: a wrapper data-attribútumai (data-gallery-title, data-image-count)
 *     segítik az LLM-eket a tartalom kontextusának értelmezésében.
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Shortcode {

	public static function init() {
		add_shortcode( 'clean_gallery',     array( __CLASS__, 'render' ) );
		add_shortcode( 'clean_gallery_all', array( __CLASS__, 'render_all' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'ids'      => '',
				'layout'   => '',
				'columns'  => 0,
				'lightbox' => '',
				'captions' => '',
				'lazy'     => '',
				'gap'      => -1,
			),
			$atts,
			'clean_gallery'
		);

		$image_ids  = array();
		$settings   = Qcg_Meta_Boxes::default_settings();
		$gallery_id = absint( $atts['id'] );
		$title      = '';

		if ( $gallery_id && Qcg_CPT::POST_TYPE === get_post_type( $gallery_id ) ) {
			$image_ids = Qcg_Meta_Boxes::get_image_ids( $gallery_id );
			$settings  = Qcg_Meta_Boxes::get_settings( $gallery_id );
			$title     = get_the_title( $gallery_id );
		} elseif ( ! empty( $atts['ids'] ) ) {
			$image_ids = array_values( array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) ) );
		}

		if ( empty( $image_ids ) ) {
			return '<!-- qaiyo-clean-gallery: no images -->';
		}

		$settings = self::apply_atts( $settings, $atts );
		Qcg_Assets::enqueue();

		ob_start();
		self::render_gallery_grid( $image_ids, $settings, $title, $gallery_id );
		return ob_get_clean();
	}

	public static function render_all( $atts ) {
		$atts = shortcode_atts(
			array(
				'card_columns'    => 3,
				'gallery_columns' => 3,
				'filter'          => 'true',
				'orderby'         => 'date',
				'order'           => 'DESC',
				'category'        => '',
				'lightbox'        => 'true',
				'captions'        => 'true',
				'lazy'            => 'true',
				'gap'             => 12,
				'per_page'        => 12,
			),
			$atts,
			'clean_gallery_all'
		);

		$card_cols    = in_array( (int) $atts['card_columns'], array( 3, 4 ), true ) ? (int) $atts['card_columns'] : 3;
		$gallery_cols = in_array( (int) $atts['gallery_columns'], array( 3, 4 ), true ) ? (int) $atts['gallery_columns'] : 3;
		$show_filter  = filter_var( $atts['filter'], FILTER_VALIDATE_BOOLEAN );
		$per_page     = max( 1, (int) $atts['per_page'] );

		$query_args = array(
			'post_type'      => Qcg_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => Qcg_CPT::TAXONOMY,
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		$galleries = get_posts( $query_args );
		if ( empty( $galleries ) ) {
			return '<p class="qcg-empty">' . esc_html__( 'No galleries available.', 'qaiyo-clean-gallery' ) . '</p>';
		}

		$all_terms = array();
		if ( $show_filter ) {
			foreach ( $galleries as $g ) {
				$terms = get_the_terms( $g->ID, Qcg_CPT::TAXONOMY );
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $t ) {
						$all_terms[ $t->slug ] = $t->name;
					}
				}
			}
			uksort( $all_terms, static function ( $a, $b ) { return (int) $b - (int) $a; } );
		}

		$date_format = get_option( 'date_format' );

		$galleries_data = array();
		foreach ( $galleries as $g ) {
			$image_ids = Qcg_Meta_Boxes::get_image_ids( $g->ID );
			if ( empty( $image_ids ) ) {
				continue;
			}

			$thumb_id  = get_post_thumbnail_id( $g->ID );
			$thumb_src = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'large' ) : null;
			if ( ! $thumb_src ) {
				$thumb_src = wp_get_attachment_image_src( $image_ids[0], 'large' );
			}

			$cat_terms = get_the_terms( $g->ID, Qcg_CPT::TAXONOMY );
			$cat_slugs = ( $cat_terms && ! is_wp_error( $cat_terms ) )
				? implode( ' ', wp_list_pluck( $cat_terms, 'slug' ) )
				: '';

			$galleries_data[] = array(
				'id'      => $g->ID,
				'title'   => $g->post_title,
				'url'     => get_permalink( $g->ID ),
				'date'    => get_the_date( $date_format, $g ),
				'date_ts' => (int) get_post_time( 'U', false, $g ),
				'thumb'   => $thumb_src ? $thumb_src[0] : '',
				'thumb_w' => $thumb_src ? (int) $thumb_src[1] : 0,
				'thumb_h' => $thumb_src ? (int) $thumb_src[2] : 0,
				'cats'    => $cat_slugs,
				'count'   => count( $image_ids ),
			);
		}

		Qcg_Assets::enqueue();

		static $instance = 0;
		++$instance;
		$uid = 'qcga-' . $instance;

		// JS i18n strings — lokalizálva a gallery script-en keresztül.
		Qcg_Assets::add_inline_i18n();

		ob_start();
		?>
		<div id="<?php echo esc_attr( $uid ); ?>"
			 class="qcg-all-wrap"
			 itemscope itemtype="https://schema.org/ItemList"
			 data-card-cols="<?php echo esc_attr( $card_cols ); ?>"
			 data-gallery-cols="<?php echo esc_attr( $gallery_cols ); ?>"
			 data-per-page="<?php echo esc_attr( $per_page ); ?>"
			 data-gap="<?php echo esc_attr( (int) $atts['gap'] ); ?>">

			<meta itemprop="numberOfItems" content="<?php echo esc_attr( count( $galleries_data ) ); ?>">

			<script type="application/json" class="qcg-all-data">
				<?php echo wp_json_encode( $galleries_data ); ?>
			</script>

			<div class="qcg-controls">
				<?php if ( $show_filter && ! empty( $all_terms ) ) : ?>
				<nav class="qcg-filter" aria-label="<?php esc_attr_e( 'Gallery filter', 'qaiyo-clean-gallery' ); ?>">
					<button type="button" class="qcg-filter-btn is-active" data-filter="*"><?php esc_html_e( 'All', 'qaiyo-clean-gallery' ); ?></button>
					<?php foreach ( $all_terms as $slug => $name ) : ?>
						<button type="button" class="qcg-filter-btn" data-filter="<?php echo esc_attr( $slug ); ?>">
							<?php echo esc_html( $name ); ?>
						</button>
					<?php endforeach; ?>
				</nav>
				<?php endif; ?>

				<div class="qcg-controls-right">
					<div class="qcg-sort-wrap">
						<select class="qcg-sort-select" aria-label="<?php esc_attr_e( 'Sort order', 'qaiyo-clean-gallery' ); ?>">
							<option value="date-desc" selected><?php esc_html_e( 'Date: newest first', 'qaiyo-clean-gallery' ); ?></option>
							<option value="date-asc"><?php esc_html_e( 'Date: oldest first', 'qaiyo-clean-gallery' ); ?></option>
							<option value="title-asc"><?php esc_html_e( 'A → Z', 'qaiyo-clean-gallery' ); ?></option>
							<option value="title-desc"><?php esc_html_e( 'Z → A', 'qaiyo-clean-gallery' ); ?></option>
						</select>
					</div>

					<div class="qcg-col-switcher" role="group" aria-label="<?php esc_attr_e( 'Columns', 'qaiyo-clean-gallery' ); ?>">
						<button type="button" class="qcg-col-btn <?php echo 3 === $card_cols ? 'is-active' : ''; ?>"
								data-cols="3" aria-label="<?php esc_attr_e( '3 columns', 'qaiyo-clean-gallery' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" fill="currentColor" aria-hidden="true">
								<rect x="20.07" y="156.85" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
								<rect x="20.07" y="430.39" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
								<rect x="293.62" y="156.85" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
								<rect x="293.62" y="430.39" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
								<rect x="567.17" y="156.85" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
								<rect x="567.17" y="430.39" width="212.76" height="212.76" rx="30.39" ry="30.39"/>
							</svg>
						</button>
						<button type="button" class="qcg-col-btn <?php echo 4 === $card_cols ? 'is-active' : ''; ?>"
								data-cols="4" aria-label="<?php esc_attr_e( '4 columns', 'qaiyo-clean-gallery' ); ?>">
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
			</div>

			<div class="qcg-card-grid" style="--qcg-card-cols:<?php echo esc_attr( $card_cols ); ?>"></div>

			<div class="qcg-load-more-wrap">
				<button type="button" class="qcg-load-more-btn" hidden>
					<?php esc_html_e( 'Load more', 'qaiyo-clean-gallery' ); ?>
					<span class="qcg-load-more-count"></span>
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function render_gallery_grid( $image_ids, $settings, $title = '', $gallery_id = 0 ) {
		static $instance = 0;
		++$instance;
		$uid      = 'qcg-' . $instance;
		$css_vars = sprintf( '--qcg-columns:%d; --qcg-gap:%dpx;', (int) $settings['columns'], (int) $settings['gap'] );
		$opts     = class_exists( 'Qcg_Settings' ) ? Qcg_Settings::get() : array();
		$classes  = array_filter(
			array(
				'clean-gallery',
				'qcg-layout-' . $settings['layout'],
				$settings['lightbox'] ? 'qcg-has-lightbox' : '',
				! empty( $opts['right_click_protect'] ) ? 'qcg-protected' : '',
			)
		);
		?>
		<?php $row_height = (int) apply_filters( 'qcg_justified_row_height', 240, $gallery_id, $settings ); ?>
		<div id="<?php echo esc_attr( $uid ); ?>"
			 class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			 style="<?php echo esc_attr( $css_vars ); ?>"
			 data-lightbox="<?php echo $settings['lightbox'] ? 'true' : 'false'; ?>"
			 data-gallery-id="<?php echo esc_attr( (int) $gallery_id ); ?>"
			 data-gallery-title="<?php echo esc_attr( $title ); ?>"
			 data-image-count="<?php echo esc_attr( count( $image_ids ) ); ?>"
			 data-row-height="<?php echo esc_attr( $row_height ); ?>"
			 itemscope itemtype="https://schema.org/ImageGallery">
			<?php if ( $title ) : ?>
				<meta itemprop="name" content="<?php echo esc_attr( $title ); ?>">
			<?php endif; ?>
			<div class="qcg-grid">
				<?php foreach ( $image_ids as $id ) : self::render_item( (int) $id, $settings ); endforeach; ?>
			</div>
			<?php if ( $settings['lightbox'] ) { self::render_lightbox(); } ?>
		</div>
		<?php
	}

	private static function render_item( $id, $settings ) {
		$full  = wp_get_attachment_image_src( $id, 'full' );
		$thumb = wp_get_attachment_image_src( $id, 'large' );
		if ( ! $full || ! $thumb ) {
			return;
		}

		$caption = wp_get_attachment_caption( $id );
		$alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
		if ( ! $alt ) {
			$alt = get_the_title( $id );
		}
		?>
		<figure class="qcg-item"
			itemprop="associatedMedia" itemscope itemtype="https://schema.org/ImageObject"
			<?php if ( $settings['lightbox'] ) : ?>
				data-full="<?php echo esc_url( $full[0] ); ?>"
				<?php if ( $caption ) : ?>
					data-caption="<?php echo esc_attr( $caption ); ?>"
				<?php endif; ?>
			<?php endif; ?>>
			<meta itemprop="contentUrl" content="<?php echo esc_url( $full[0] ); ?>">
			<div class="qcg-thumb-wrap">
				<img src="<?php echo esc_url( $thumb[0] ); ?>"
					 alt="<?php echo esc_attr( $alt ); ?>"
					 width="<?php echo esc_attr( (int) $thumb[1] ); ?>"
					 height="<?php echo esc_attr( (int) $thumb[2] ); ?>"
					 itemprop="thumbnail"
					 <?php echo $settings['lazy'] ? 'loading="lazy"' : ''; ?>>
				<?php if ( $settings['lightbox'] ) : ?>
					<div class="qcg-overlay" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
							<path d="M15 3h6v6h-2V5h-4V3zM9 3H3v6h2V5h4V3zM3 15h2v4h4v2H3v-6zm16 4h-4v2h6v-6h-2v4z"/>
						</svg>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $settings['captions'] && $caption ) : ?>
				<figcaption class="qcg-caption" itemprop="caption"><?php echo esc_html( $caption ); ?></figcaption>
			<?php endif; ?>
		</figure>
		<?php
	}

	private static function render_lightbox() {
		?>
		<div class="qcg-lightbox" role="dialog" aria-modal="true" hidden>
			<button type="button" class="qcg-lb-close" aria-label="<?php esc_attr_e( 'Close', 'qaiyo-clean-gallery' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
			</button>
			<button type="button" class="qcg-lb-prev" aria-label="<?php esc_attr_e( 'Previous', 'qaiyo-clean-gallery' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
			</button>
			<div class="qcg-lb-stage">
				<img class="qcg-lb-img" src="" alt="">
				<p class="qcg-lb-caption"></p>
			</div>
			<button type="button" class="qcg-lb-next" aria-label="<?php esc_attr_e( 'Next', 'qaiyo-clean-gallery' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
			</button>
			<div class="qcg-lb-counter" aria-live="polite"></div>

			<div class="qcg-lb-share" hidden>
				<button type="button" class="qcg-share-btn" data-network="facebook" aria-label="Facebook">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3V2z"/></svg>
				</button>
				<button type="button" class="qcg-share-btn" data-network="x" aria-label="X">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
				</button>
				<button type="button" class="qcg-share-btn" data-network="pinterest" aria-label="Pinterest">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/></svg>
				</button>
				<button type="button" class="qcg-share-btn" data-network="copy" aria-label="<?php esc_attr_e( 'Copy link', 'qaiyo-clean-gallery' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
				</button>
			</div>
		</div>
		<?php
	}

	private static function apply_atts( $settings, $atts ) {
		if ( ! empty( $atts['layout'] ) && in_array( $atts['layout'], Qcg_Meta_Boxes::allowed_layouts(), true ) ) {
			$settings['layout'] = $atts['layout'];
		}
		if ( (int) $atts['columns'] > 0 ) {
			$settings['columns'] = min( 5, max( 2, (int) $atts['columns'] ) );
		}
		if ( isset( $atts['lightbox'] ) && '' !== $atts['lightbox'] ) {
			$settings['lightbox'] = filter_var( $atts['lightbox'], FILTER_VALIDATE_BOOLEAN );
		}
		if ( isset( $atts['captions'] ) && '' !== $atts['captions'] ) {
			$settings['captions'] = filter_var( $atts['captions'], FILTER_VALIDATE_BOOLEAN );
		}
		if ( isset( $atts['lazy'] ) && '' !== $atts['lazy'] ) {
			$settings['lazy'] = filter_var( $atts['lazy'], FILTER_VALIDATE_BOOLEAN );
		}
		if ( isset( $atts['gap'] ) && (int) $atts['gap'] >= 0 ) {
			$settings['gap'] = min( 60, (int) $atts['gap'] );
		}
		return $settings;
	}
}

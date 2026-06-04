<?php
/**
 * Qaiyo Clean Gallery – Single template (fallback).
 *
 * A téma override-olhatja: másold át a saját témád root mappájába
 * single-clean_gallery.php néven.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$title     = get_the_title();
	$date_fmt  = get_option( 'date_format' );
	$date      = get_the_date( $date_fmt );
	$image_ids = Qcg_Meta_Boxes::get_image_ids( $post_id );
	$settings  = Qcg_Meta_Boxes::get_settings( $post_id );

	$cat_terms = get_the_terms( $post_id, Qcg_CPT::TAXONOMY );
	$cat_names = ( $cat_terms && ! is_wp_error( $cat_terms ) )
		? implode( ', ', wp_list_pluck( $cat_terms, 'name' ) )
		: '';

	$hero_id = get_post_thumbnail_id( $post_id );
	if ( ! $hero_id && ! empty( $image_ids ) ) {
		$hero_id = $image_ids[0];
	}
	$hero_src = $hero_id ? wp_get_attachment_image_src( $hero_id, 'full' ) : null;

	// Back link csak ugyanonnan, sanitizálva
	$back_url   = '';
	$back_label = __( 'Back to galleries', 'qaiyo-clean-gallery' );
	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$ref = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		if ( $ref && wp_validate_redirect( $ref, '' ) === $ref ) {
			if ( false !== strpos( $ref, home_url() ) && false === strpos( $ref, '/' . Qcg_CPT::slug() . '/' ) ) {
				$back_url = $ref;
			}
		}
	}

	Qcg_Assets::enqueue();
	?>

	<style>
	.qcg-single-wrap {
		--qcg-hero-height:       60vh;
		--qcg-hero-overlay:      rgba(108,92,231,.45);
		--qcg-hero-title-color:  #fff;
		--qcg-single-max-width:  1200px;
		--qcg-single-padding:    clamp(16px, 4vw, 48px);
		--qcg-back-color:        #555;
		--qcg-back-hover:        #6c5ce7;
		--qcg-meta-color:        #888;
	}
	.qcg-single-hero {
		position: relative; width: 100%;
		height: var(--qcg-hero-height);
		min-height: 240px; max-height: 600px;
		overflow: hidden;
		background: #1a1a1a;
		display: flex; align-items: flex-end;
	}
	.qcg-single-hero-img {
		position: absolute; inset: 0;
		width: 100%; height: 100%;
		object-fit: cover; object-position: center;
		display: block;
	}
	.qcg-single-hero-overlay { position: absolute; inset: 0; background: var(--qcg-hero-overlay); }
	.qcg-single-hero-content {
		position: relative; z-index: 2;
		width: 100%;
		max-width: var(--qcg-single-max-width);
		margin: 0 auto;
		padding: var(--qcg-single-padding);
		padding-bottom: clamp(20px, 5vh, 48px);
	}
	.qcg-single-hero h1 {
		color: var(--qcg-hero-title-color);
		font-size: clamp(1.5rem, 4vw, 2.75rem);
		font-weight: 700; line-height: 1.15;
		margin: 0 0 .5rem;
		text-shadow: 0 2px 8px rgba(0,0,0,.4);
	}
	.qcg-single-meta {
		color: rgba(255,255,255,.85);
		font-size: .9rem;
		display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
	}
	.qcg-single-meta-sep { opacity: .5; }
	.qcg-single-hero.qcg-no-hero { height: auto; min-height: 0; background: #f8f6ff; align-items: center; }
	.qcg-single-hero.qcg-no-hero h1 { color: #1a1a1a; text-shadow: none; }
	.qcg-single-hero.qcg-no-hero .qcg-single-meta { color: #50575e; }
	.qcg-single-body { max-width: var(--qcg-single-max-width); margin: 0 auto; padding: var(--qcg-single-padding); }
	.qcg-single-back {
		display: inline-flex; align-items: center; gap: 6px;
		color: var(--qcg-back-color);
		text-decoration: none; font-size: .875rem;
		margin-bottom: 1.5rem;
		transition: color .2s;
	}
	.qcg-single-back:hover { color: var(--qcg-back-hover); }
	.qcg-single-back svg { width: 1em; height: 1em; flex-shrink: 0; }
	.qcg-single-count { font-size: .875rem; color: var(--qcg-meta-color); margin-bottom: 1.25rem; }
	@media (max-width: 640px) { .qcg-single-hero { --qcg-hero-height: 45vw; } }
	</style>

	<article class="qcg-single-wrap" itemscope itemtype="https://schema.org/ImageGallery">

		<div class="qcg-single-hero <?php echo $hero_src ? '' : 'qcg-no-hero'; ?>">
			<?php if ( $hero_src ) : ?>
				<img class="qcg-single-hero-img"
					 src="<?php echo esc_url( $hero_src[0] ); ?>"
					 alt="<?php echo esc_attr( $title ); ?>"
					 width="<?php echo esc_attr( (int) $hero_src[1] ); ?>"
					 height="<?php echo esc_attr( (int) $hero_src[2] ); ?>">
				<div class="qcg-single-hero-overlay"></div>
			<?php endif; ?>

			<div class="qcg-single-hero-content">
				<h1 itemprop="name"><?php echo esc_html( $title ); ?></h1>
				<div class="qcg-single-meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>" itemprop="datePublished">
						<?php echo esc_html( $date ); ?>
					</time>
					<?php if ( $cat_names ) : ?>
						<span class="qcg-single-meta-sep">·</span>
						<span itemprop="keywords"><?php echo esc_html( $cat_names ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $image_ids ) ) : ?>
						<span class="qcg-single-meta-sep">·</span>
						<span>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: image count */
									_n( '%d image', '%d images', count( $image_ids ), 'qaiyo-clean-gallery' ),
									count( $image_ids )
								)
							);
							?>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="qcg-single-body">

			<?php if ( $back_url ) : ?>
				<a href="<?php echo esc_url( $back_url ); ?>" class="qcg-single-back">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
						 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M19 12H5M12 5l-7 7 7 7"/>
					</svg>
					<?php echo esc_html( $back_label ); ?>
				</a>
			<?php endif; ?>

			<?php if ( empty( $image_ids ) ) : ?>
				<p><?php esc_html_e( 'No images uploaded for this gallery yet.', 'qaiyo-clean-gallery' ); ?></p>
			<?php else : ?>
				<?php echo do_shortcode( '[clean_gallery id="' . (int) $post_id . '"]' ); ?>
			<?php endif; ?>

		</div>
	</article>

	<?php
endwhile;

get_footer();

<?php
/**
 * Qaiyo Clean Gallery – Gutenberg block (server-side render).
 *
 * Block név: qaiyo-clean-gallery/gallery
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Block {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'qcg-block',
			QCG_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-server-side-render', 'wp-i18n' ),
			QCG_VERSION,
			true
		);

		wp_localize_script(
			'qcg-block',
			'qcgBlock',
			array(
				'galleries' => self::get_gallery_list(),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'qcg-block', 'qaiyo-clean-gallery', QCG_DIR . 'languages' );
		}

		register_block_type(
			'qaiyo-clean-gallery/gallery',
			array(
				'api_version'     => 3,
				'title'           => __( 'Qaiyo Clean Gallery', 'qaiyo-clean-gallery' ),
				'description'     => __( 'Display a gallery with grid or masonry layout, lightbox and filtering.', 'qaiyo-clean-gallery' ),
				'category'        => 'media',
				'icon'            => 'format-gallery',
				'keywords'        => array( 'gallery', 'images', 'masonry', 'lightbox', 'grid', 'qaiyo' ),
				'supports'        => array(
					'html'  => false,
					'align' => array( 'wide', 'full' ),
				),
				'attributes'      => array(
					'galleryId'  => array( 'type' => 'integer', 'default' => 0 ),
					'layout'     => array( 'type' => 'string',  'default' => 'grid', 'enum' => array( 'grid', 'masonry', 'standard', 'justified', 'bento' ) ),
					'columns'    => array( 'type' => 'integer', 'default' => 3 ),
					'lightbox'   => array( 'type' => 'boolean', 'default' => true ),
					'captions'   => array( 'type' => 'boolean', 'default' => true ),
					'lazy'       => array( 'type' => 'boolean', 'default' => true ),
					'gap'        => array( 'type' => 'integer', 'default' => 12 ),
					'showFilter' => array( 'type' => 'boolean', 'default' => false ),
				),
				'render_callback' => array( __CLASS__, 'render' ),
				'editor_script'   => 'qcg-block',
				'editor_style'    => 'qcg-gallery',
				'style'           => 'qcg-gallery',
				'script'          => 'qcg-gallery',
			)
		);
	}

	public static function render( $attributes ) {
		$atts = array(
			'id'       => isset( $attributes['galleryId'] ) ? (int) $attributes['galleryId'] : 0,
			'layout'   => isset( $attributes['layout'] ) ? $attributes['layout'] : 'grid',
			'columns'  => isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3,
			'lightbox' => ! empty( $attributes['lightbox'] ) ? 'true' : 'false',
			'captions' => ! empty( $attributes['captions'] ) ? 'true' : 'false',
			'lazy'     => ! empty( $attributes['lazy'] ) ? 'true' : 'false',
			'gap'      => isset( $attributes['gap'] ) ? (int) $attributes['gap'] : 12,
			'filter'   => ! empty( $attributes['showFilter'] ) ? 'true' : 'false',
		);

		Qcg_Assets::enqueue();
		return Qcg_Shortcode::render( $atts );
	}

	private static function get_gallery_list() {
		$posts = get_posts(
			array(
				'post_type'      => Qcg_CPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$list = array(
			array(
				'value' => 0,
				'label' => __( '— Select gallery —', 'qaiyo-clean-gallery' ),
			),
		);

		foreach ( $posts as $post ) {
			$list[] = array(
				'value' => (int) $post->ID,
				'label' => $post->post_title,
			);
		}

		return $list;
	}
}

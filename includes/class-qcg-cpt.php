<?php
/**
 * Qaiyo Clean Gallery – CPT + Taxonomy
 *
 * Post type: clean_gallery   (megőrizve a kompatibilitás miatt)
 * Taxonomy:  gallery_category
 * Single URL: /galeria/{slug}/ (filterezhető: qcg_cpt_slug)
 */

defined( 'ABSPATH' ) || exit;

class Qcg_CPT {

	const POST_TYPE = 'clean_gallery';
	const TAXONOMY  = 'gallery_category';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
	}

	public static function slug() {
		return apply_filters( 'qcg_cpt_slug', 'galeria' );
	}

	public static function tax_slug() {
		return apply_filters( 'qcg_tax_slug', self::slug() . '-kategoria' );
	}

	public static function register_post_type() {
		// Register this plugin's top-level menu slug with the shared Qaiyo brand menu,
		// so the separators can wrap our menu item even when multiple Qaiyo plugins are active.
		if ( class_exists( 'Qcg_Brand_Menu' ) ) {
			Qcg_Brand_Menu::register_plugin_slug( 'edit.php?post_type=' . self::POST_TYPE );
		}

		$labels = array(
			'name'               => __( 'Galleries', 'qaiyo-clean-gallery' ),
			'singular_name'      => __( 'Gallery', 'qaiyo-clean-gallery' ),
			'add_new'            => __( 'Add New', 'qaiyo-clean-gallery' ),
			'add_new_item'       => __( 'Add New Gallery', 'qaiyo-clean-gallery' ),
			'edit_item'          => __( 'Edit Gallery', 'qaiyo-clean-gallery' ),
			'new_item'           => __( 'New Gallery', 'qaiyo-clean-gallery' ),
			'view_item'          => __( 'View Gallery', 'qaiyo-clean-gallery' ),
			'search_items'       => __( 'Search Galleries', 'qaiyo-clean-gallery' ),
			'not_found'          => __( 'No galleries found.', 'qaiyo-clean-gallery' ),
			'not_found_in_trash' => __( 'No galleries in Trash.', 'qaiyo-clean-gallery' ),
			'menu_name'          => __( 'Clean Gallery', 'qaiyo-clean-gallery' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'menu_icon'          => 'dashicons-format-gallery',
				'menu_position'      => class_exists( 'Qcg_Brand_Menu' ) ? Qcg_Brand_Menu::plugin_position() : 25,
				'supports'           => array( 'title', 'thumbnail' ),
				'has_archive'        => false,
				'rewrite'            => array(
					'slug'       => self::slug(),
					'with_front' => false,
				),
				'show_in_rest'       => true,
				'capability_type'    => 'post',
			)
		);
	}

	public static function register_taxonomy() {
		$labels = array(
			'name'          => __( 'Gallery Categories', 'qaiyo-clean-gallery' ),
			'singular_name' => __( 'Gallery Category', 'qaiyo-clean-gallery' ),
			'search_items'  => __( 'Search Categories', 'qaiyo-clean-gallery' ),
			'all_items'     => __( 'All Categories', 'qaiyo-clean-gallery' ),
			'edit_item'     => __( 'Edit Category', 'qaiyo-clean-gallery' ),
			'update_item'   => __( 'Update Category', 'qaiyo-clean-gallery' ),
			'add_new_item'  => __( 'Add New Category', 'qaiyo-clean-gallery' ),
			'new_item_name' => __( 'New Category Name', 'qaiyo-clean-gallery' ),
			'menu_name'     => __( 'Categories', 'qaiyo-clean-gallery' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => self::tax_slug() ),
			)
		);
	}

	public static function flush_rules() {
		self::register_post_type();
		self::register_taxonomy();
		flush_rewrite_rules();
	}
}

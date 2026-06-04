<?php
/**
 * Qaiyo Clean Gallery – Single template support.
 *
 * A CPT publikus, /galeria/{slug}/ URL-en. A rendelést a téma single.php
 * vagy single-clean_gallery.php intézi (a templates/single-clean_gallery.php
 * fájlt a téma override-olhatja).
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Single_Template {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_filter( 'single_template', array( __CLASS__, 'load_default_template' ) );
	}

	public static function maybe_enqueue() {
		if ( is_singular( Qcg_CPT::POST_TYPE ) ) {
			Qcg_Assets::enqueue();
		}
	}

	/**
	 * Ha a téma nem ad single-clean_gallery.php-t, betöltjük a sajátunkat.
	 */
	public static function load_default_template( $template ) {
		if ( ! is_singular( Qcg_CPT::POST_TYPE ) ) {
			return $template;
		}

		// A téma override-ja nyer.
		$theme_template = locate_template( array( 'single-' . Qcg_CPT::POST_TYPE . '.php' ) );
		if ( $theme_template ) {
			return $theme_template;
		}

		$plugin_template = QCG_DIR . 'templates/single-clean_gallery.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}

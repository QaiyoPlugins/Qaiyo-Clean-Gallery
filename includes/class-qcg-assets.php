<?php
/**
 * Qaiyo Clean Gallery – Frontend asset (CSS/JS) regisztráció és enqueue.
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Assets {

	private static $i18n_added = false;

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		wp_register_style(
			'qcg-gallery',
			QCG_URL . 'assets/css/gallery.css',
			array(),
			QCG_VERSION
		);

		wp_register_script(
			'qcg-gallery',
			QCG_URL . 'assets/js/gallery.js',
			array(),
			QCG_VERSION,
			array( 'in_footer' => true )
		);
	}

	public static function enqueue() {
		wp_enqueue_style( 'qcg-gallery' );
		wp_enqueue_script( 'qcg-gallery' );
		self::add_inline_i18n();
	}

	public static function add_inline_i18n() {
		if ( self::$i18n_added ) {
			return;
		}
		self::$i18n_added = true;

		$i18n = array(
			'all'              => __( 'All', 'qaiyo-clean-gallery' ),
			'images'           => __( 'images', 'qaiyo-clean-gallery' ),
			/* translators: 1: next batch count, 2: remaining count */
			'remaining'        => __( '(%1$d / %2$d remaining)', 'qaiyo-clean-gallery' ),
			'openGallery'      => __( 'Open gallery', 'qaiyo-clean-gallery' ),
		);

		wp_localize_script( 'qcg-gallery', 'qcgI18n', $i18n );
	}
}

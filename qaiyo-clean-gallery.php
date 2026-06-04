<?php
/**
 * Plugin Name: Qaiyo Clean Gallery
 * Plugin URI: https://qaiyo-plugins.com/plugins/clean-gallery/
 * Description: Letisztult, gyors galéria plugin (Grid, Masonry, Lightbox, szűrő, lazy loading) – nulla függőséggel. Saját CPT, blokk és shortcode. HappyFiles plugin kompatibilitás.
 * Version: 0.6.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.0
 * Author: Qaiyo by PixelDesigns
 * Author URI: https://qaiyo-plugins.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: qaiyo-clean-gallery
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'QCG_VERSION',     '0.6.1' );
define( 'QCG_DIR',         plugin_dir_path( __FILE__ ) );
define( 'QCG_URL',         plugin_dir_url( __FILE__ ) );
define( 'QCG_FILE',        __FILE__ );
define( 'QCG_TEXT_DOMAIN', 'qaiyo-clean-gallery' );

// Core includes
require_once QCG_DIR . 'includes/class-qcg-i18n.php';
require_once QCG_DIR . 'includes/class-qcg-brand-menu.php';
require_once QCG_DIR . 'includes/class-qcg-cpt.php';
require_once QCG_DIR . 'includes/class-qcg-meta-boxes.php';
require_once QCG_DIR . 'includes/class-qcg-shortcode.php';
require_once QCG_DIR . 'includes/class-qcg-single-template.php';
require_once QCG_DIR . 'includes/class-qcg-assets.php';
require_once QCG_DIR . 'includes/class-qcg-block.php';
require_once QCG_DIR . 'includes/class-qcg-image-optimizer.php';
require_once QCG_DIR . 'includes/class-qcg-happyfiles.php';
require_once QCG_DIR . 'includes/class-qcg-settings.php';

// i18n bootstrap (uses load_textdomain, NOT load_plugin_textdomain — Plugin Check requirement)
Qcg_I18n::init();

// Brand menu (Qaiyo plugin family separator/label)
Qcg_Brand_Menu::init();

// Boot
add_action( 'plugins_loaded', static function () {
	Qcg_CPT::init();
	Qcg_Meta_Boxes::init();
	Qcg_Shortcode::init();
	Qcg_Single_Template::init();
	Qcg_Assets::init();
	Qcg_Block::init();
	Qcg_Image_Optimizer::init();
	Qcg_HappyFiles::init();
	Qcg_Settings::init();
}, 10 );

// Optimizer meta carry-over from upload pipeline → attachment meta
add_action( 'add_attachment', array( 'Qcg_Image_Optimizer', 'save_attachment_meta' ) );

// ACF field group hiding on gallery CPT
add_filter( 'acf/location/rule_match', static function ( $match, $rule, $options, $field_group ) {
	if ( isset( $options['post_type'] ) && 'clean_gallery' === $options['post_type'] ) {
		return false;
	}
	return $match;
}, 10, 4 );

// Activation / deactivation
register_activation_hook( __FILE__,   array( 'Qcg_CPT', 'flush_rules' ) );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

<?php
/**
 * Qaiyo Clean Gallery – i18n loader (Qaiyo szabvány)
 *
 * Gyári nyelvek: en_US, hu_HU, de_DE, fr_FR, es_ES
 * Locale variánsok az alap locale-ra mappelve, ha nincs egyezés → angol fallback.
 */

defined( 'ABSPATH' ) || exit;

class Qcg_I18n {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'load_translations' ), 1 );
	}

	public static function load_translations() {
		$locale = determine_locale();

		$locale_map = array(
			'de_CH'        => 'de_DE',
			'de_AT'        => 'de_DE',
			'de_DE_formal' => 'de_DE',
			'fr_BE'        => 'fr_FR',
			'fr_CA'        => 'fr_FR',
			'fr_CH'        => 'fr_FR',
			'es_MX'        => 'es_ES',
			'es_AR'        => 'es_ES',
			'es_CO'        => 'es_ES',
			'es_CL'        => 'es_ES',
			'es_PE'        => 'es_ES',
			'es_VE'        => 'es_ES',
			'es_UY'        => 'es_ES',
		);

		if ( isset( $locale_map[ $locale ] ) ) {
			$locale = $locale_map[ $locale ];
		}

		$supported = array( 'en_US', 'hu_HU', 'de_DE', 'fr_FR', 'es_ES' );

		if ( ! in_array( $locale, $supported, true ) || 'en_US' === $locale ) {
			return;
		}

		$mofile = QCG_DIR . 'languages/' . QCG_TEXT_DOMAIN . '-' . $locale . '.mo';
		if ( file_exists( $mofile ) ) {
			load_textdomain( QCG_TEXT_DOMAIN, $mofile );
		}
	}
}

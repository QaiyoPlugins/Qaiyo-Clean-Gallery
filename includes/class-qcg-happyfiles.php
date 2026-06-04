<?php
/**
 * Qaiyo Clean Gallery – HappyFiles plugin kompatibilitás.
 *
 * Detektálja a HappyFiles (free vagy Pro) telepítését. Ha aktív:
 *   - A galéria szerkesztő képbeszúró rész mellé "Import from HappyFiles folder"
 *     dropdown + gomb kerül.
 *   - AJAX végpont: qcg_hf_import → visszaadja a kiválasztott folder összes
 *     attachment ID-ját, hogy a kliens hozzáfűzze a galériához.
 *
 * Támogatott taxonomy slugok (HappyFiles dokumentáció szerint):
 *   - 'happyfiles_category'  (Free)
 *   - 'happyfiles'           (Pro: extra taxonomy lehetőség)
 */

defined( 'ABSPATH' ) || exit;

class Qcg_HappyFiles {

	public static function init() {
		add_action( 'wp_ajax_qcg_hf_import', array( __CLASS__, 'ajax_import' ) );
	}

	/**
	 * HappyFiles aktív? (free vagy pro, vagy bármely más plugin a happyfiles taxonomyval)
	 */
	public static function is_available() {
		return taxonomy_exists( self::taxonomy() );
	}

	public static function taxonomy() {
		$candidates = array( 'happyfiles_category', 'happyfiles' );
		foreach ( $candidates as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				return apply_filters( 'qcg_happyfiles_taxonomy', $tax );
			}
		}
		return apply_filters( 'qcg_happyfiles_taxonomy', 'happyfiles_category' );
	}

	/**
	 * Lekéri az összes HappyFiles foldert hierarchikus sorrendben.
	 *
	 * @return array list of ['id'=>int, 'name'=>string, 'depth'=>int, 'count'=>int]
	 */
	public static function get_folders() {
		if ( ! self::is_available() ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => self::taxonomy(),
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		// Hierarchikus rendezés
		$by_parent = array();
		foreach ( $terms as $t ) {
			$by_parent[ (int) $t->parent ][] = $t;
		}

		$flat = array();
		self::flatten_terms( $by_parent, 0, 0, $flat );

		return $flat;
	}

	private static function flatten_terms( $by_parent, $parent, $depth, &$out ) {
		if ( empty( $by_parent[ $parent ] ) ) {
			return;
		}
		foreach ( $by_parent[ $parent ] as $term ) {
			$out[] = array(
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'depth' => $depth,
				'count' => (int) $term->count,
			);
			self::flatten_terms( $by_parent, (int) $term->term_id, $depth + 1, $out );
		}
	}

	/**
	 * Egy HappyFiles folder összes (publikált, image) attachment ID-ja.
	 * Élő lekérdezés — a live-link mód használja a renderelésnél.
	 *
	 * @param int $folder_id Term ID.
	 * @return int[]
	 */
	public static function get_folder_image_ids( $folder_id ) {
		$folder_id = (int) $folder_id;
		if ( ! $folder_id || ! self::is_available() ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => apply_filters( 'qcg_hf_orderby', 'date' ),
				'order'          => apply_filters( 'qcg_hf_order', 'ASC' ),
				'tax_query'      => array(
					array(
						'taxonomy' => self::taxonomy(),
						'field'    => 'term_id',
						'terms'    => $folder_id,
					),
				),
			)
		);

		return array_values( array_map( 'absint', $query->posts ) );
	}

	public static function ajax_import() {
		check_ajax_referer( 'qcg_admin', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'qaiyo-clean-gallery' ) ), 403 );
		}

		if ( ! self::is_available() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'HappyFiles plugin is not active.', 'qaiyo-clean-gallery' ) ), 400 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified via check_ajax_referer.
		$folder_id = isset( $_POST['folder_id'] ) ? absint( wp_unslash( $_POST['folder_id'] ) ) : 0;
		if ( ! $folder_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid folder.', 'qaiyo-clean-gallery' ) ), 400 );
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => self::taxonomy(),
						'field'    => 'term_id',
						'terms'    => $folder_id,
					),
				),
			)
		);

		$ids = array_map( 'absint', $query->posts );

		$payload = array();
		foreach ( $ids as $id ) {
			$thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
			if ( ! $thumb ) {
				continue;
			}
			$payload[] = array(
				'id'  => $id,
				'url' => $thumb[0],
			);
		}

		wp_send_json_success( array( 'images' => $payload ) );
	}
}

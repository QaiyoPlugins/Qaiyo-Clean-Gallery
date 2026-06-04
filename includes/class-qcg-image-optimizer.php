<?php
/**
 * Qaiyo Clean Gallery – Image Optimizer.
 *
 * Automatikus képtömörítés a feltöltés után:
 *   - 1.5 MB feletti képeket 1 MB alá tömörít (Imagick > GD fallback).
 *   - JPEG, PNG, WebP támogatott.
 */

defined( 'ABSPATH' ) || exit;

class Qcg_Image_Optimizer {

	const THRESHOLD_BYTES = 1572864;  // 1.5 MB
	const MAX_BYTES       = 1048576;  // 1 MB
	const MIN_BYTES       = 512000;   // 500 KB
	const QUALITY_STEP    = 5;
	const QUALITY_FLOOR   = 30;
	const META_KEY        = '_clean_gallery_optimizer';

	public static function init() {
		add_filter( 'wp_handle_upload', array( __CLASS__, 'handle_upload' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'show_attachment_notice' ), 10, 2 );
	}

	public static function handle_upload( $upload, $context ) {
		if ( 'upload' !== $context ) {
			return $upload;
		}
		if ( ! isset( $upload['type'] ) || 0 !== strpos( $upload['type'], 'image/' ) ) {
			return $upload;
		}
		$file_path = isset( $upload['file'] ) ? $upload['file'] : '';
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return $upload;
		}

		$file_size = filesize( $file_path );
		$threshold = (int) apply_filters( 'qcg_compress_threshold', self::THRESHOLD_BYTES );

		if ( $file_size <= $threshold ) {
			return $upload;
		}

		$original_size = $file_size;
		$result        = self::compress( $file_path, $upload['type'] );

		if ( is_wp_error( $result ) ) {
			self::store_meta_for_path(
				$file_path,
				array(
					'status'        => 'error',
					'error'         => $result->get_error_message(),
					'original_size' => $original_size,
				)
			);
			return $upload;
		}

		$new_size = filesize( $file_path );
		self::store_meta_for_path(
			$file_path,
			array(
				'status'        => 'compressed',
				'original_size' => $original_size,
				'new_size'      => $new_size,
				'engine'        => $result,
			)
		);

		return $upload;
	}

	public static function compress( $path, $mime_type ) {
		if ( extension_loaded( 'imagick' ) && class_exists( '\Imagick' ) ) {
			$result = self::compress_imagick( $path, $mime_type );
			if ( ! is_wp_error( $result ) ) {
				return 'imagick';
			}
		}

		if ( extension_loaded( 'gd' ) && function_exists( 'imagecreatefromjpeg' ) ) {
			$result = self::compress_gd( $path, $mime_type );
			if ( ! is_wp_error( $result ) ) {
				return 'gd';
			}
		}

		return new WP_Error( 'qcg_no_image_library', __( 'Neither Imagick nor GD is available on this server.', 'qaiyo-clean-gallery' ) );
	}

	private static function compress_imagick( $path, $mime_type ) {
		try {
			$max   = (int) apply_filters( 'qcg_compress_max_bytes', self::MAX_BYTES );
			$step  = (int) apply_filters( 'qcg_compress_quality_step', self::QUALITY_STEP );
			$floor = (int) apply_filters( 'qcg_compress_quality_floor', self::QUALITY_FLOOR );

			$imagick = new \Imagick( $path );
			$imagick->stripImage();
			$imagick->autoOrient();

			$quality = 85;

			do {
				$imagick->setImageCompressionQuality( $quality );
				$tmp = $path . '.qcg_tmp';
				$imagick->writeImage( $tmp );

				$size = filesize( $tmp );

				if ( $size <= $max ) {
					rename( $tmp, $path );
					$imagick->destroy();
					return true;
				}

				wp_delete_file( $tmp );
				$quality -= $step;
			} while ( $quality >= $floor );

			$imagick->setImageCompressionQuality( $floor );
			$imagick->writeImage( $path );
			$imagick->destroy();

			return true;

		} catch ( \Exception $e ) {
			return new WP_Error( 'qcg_imagick_error', $e->getMessage() );
		}
	}

	private static function compress_gd( $path, $mime_type ) {
		$max   = (int) apply_filters( 'qcg_compress_max_bytes', self::MAX_BYTES );
		$step  = (int) apply_filters( 'qcg_compress_quality_step', self::QUALITY_STEP );
		$floor = (int) apply_filters( 'qcg_compress_quality_floor', self::QUALITY_FLOOR );

		$image = self::gd_load( $path, $mime_type );
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		$quality = 85;

		do {
			ob_start();

			switch ( $mime_type ) {
				case 'image/jpeg':
					imagejpeg( $image, null, $quality );
					break;
				case 'image/png':
					$png_quality = (int) round( ( 100 - $quality ) / 11.111 );
					imagepng( $image, null, min( 9, max( 0, $png_quality ) ) );
					break;
				case 'image/webp':
					if ( function_exists( 'imagewebp' ) ) {
						imagewebp( $image, null, $quality );
					} else {
						ob_end_clean();
						imagedestroy( $image );
						return new WP_Error( 'qcg_gd_no_webp', __( 'GD WebP support not compiled in.', 'qaiyo-clean-gallery' ) );
					}
					break;
				default:
					ob_end_clean();
					imagedestroy( $image );
					return new WP_Error( 'qcg_unsupported_type', __( 'Unsupported image type for GD compression.', 'qaiyo-clean-gallery' ) );
			}

			$data = ob_get_clean();
			$size = strlen( $data );

			if ( $size <= $max ) {
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				global $wp_filesystem;
				WP_Filesystem();
				$wp_filesystem->put_contents( $path, $data, FS_CHMOD_FILE );
				imagedestroy( $image );
				return true;
			}

			$quality -= $step;
		} while ( $quality >= $floor );

		ob_start();
		imagejpeg( $image, null, $floor );
		$data = ob_get_clean();
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		WP_Filesystem();
		$wp_filesystem->put_contents( $path, $data, FS_CHMOD_FILE );
		imagedestroy( $image );

		return true;
	}

	private static function gd_load( $path, $mime_type ) {
		switch ( $mime_type ) {
			case 'image/jpeg':
				return imagecreatefromjpeg( $path );
			case 'image/png':
				return imagecreatefrompng( $path );
			case 'image/webp':
				if ( function_exists( 'imagecreatefromwebp' ) ) {
					return imagecreatefromwebp( $path );
				}
				return new WP_Error( 'qcg_gd_no_webp', __( 'GD WebP support not available.', 'qaiyo-clean-gallery' ) );
			default:
				return new WP_Error( 'qcg_unsupported_type', __( 'Unsupported image type.', 'qaiyo-clean-gallery' ) );
		}
	}

	private static function store_meta_for_path( $path, $data ) {
		$key          = 'qcg_upload_' . md5( $path );
		$data['path'] = $path;
		set_transient( $key, $data, HOUR_IN_SECONDS );
	}

	public static function get_meta_for_path( $path ) {
		$key = 'qcg_upload_' . md5( $path );
		$val = get_transient( $key );
		return is_array( $val ) ? $val : null;
	}

	public static function save_attachment_meta( $attachment_id ) {
		$path = get_attached_file( $attachment_id );
		if ( ! $path ) {
			return;
		}
		$data = self::get_meta_for_path( $path );
		if ( ! $data ) {
			return;
		}
		update_post_meta( $attachment_id, self::META_KEY, $data );
		delete_transient( 'qcg_upload_' . md5( $path ) );
	}

	public static function show_attachment_notice( $fields, $post ) {
		$data = get_post_meta( $post->ID, self::META_KEY, true );
		if ( ! $data || ! is_array( $data ) ) {
			return $fields;
		}

		if ( 'compressed' === ( isset( $data['status'] ) ? $data['status'] : '' ) ) {
			$saved = $data['original_size'] - $data['new_size'];
			$pct   = $data['original_size'] > 0
				? round( ( $saved / $data['original_size'] ) * 100 )
				: 0;

			$notice = sprintf(
				/* translators: 1: original file size, 2: new file size, 3: percent saved, 4: engine name */
				__( 'Optimized by Qaiyo Clean Gallery: %1$s → %2$s (saved %3$d%%, engine: %4$s)', 'qaiyo-clean-gallery' ),
				size_format( $data['original_size'] ),
				size_format( $data['new_size'] ),
				$pct,
				isset( $data['engine'] ) ? $data['engine'] : 'unknown'
			);

			$fields['qcg_optimizer'] = array(
				'label' => __( 'Qaiyo Clean Gallery', 'qaiyo-clean-gallery' ),
				'input' => 'html',
				'html'  => '<span style="color:#00a32a;font-weight:500">' . esc_html( $notice ) . '</span>',
			);
		} elseif ( 'error' === ( isset( $data['status'] ) ? $data['status'] : '' ) ) {
			$fields['qcg_optimizer'] = array(
				'label' => __( 'Qaiyo Clean Gallery', 'qaiyo-clean-gallery' ),
				'input' => 'html',
				'html'  => '<span style="color:#d63638">' . esc_html__( 'Compression failed: ', 'qaiyo-clean-gallery' ) . esc_html( isset( $data['error'] ) ? $data['error'] : '' ) . '</span>',
			);
		}

		return $fields;
	}
}

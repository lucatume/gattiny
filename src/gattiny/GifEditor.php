<?php

include_once ABSPATH . '/wp-includes/class-wp-image-editor.php';
include_once ABSPATH . '/wp-includes/class-wp-image-editor-imagick.php';

class gattiny_GifEditor extends WP_Image_Editor_Imagick {

	public static function test( $args = array() ) {
		$mimeTypeIsGif      = ! empty( $args['mime_type'] ) && $args['mime_type'] === 'image/gif';
		$hasRequiredMethods = method_exists( 'Imagick', 'cropThumbnailImage' );

		return parent::test( $args ) && $mimeTypeIsGif && $hasRequiredMethods;
	}

	public function multi_resize( $sizes ) {
		$metadata     = array();
		$original     = $this->image;
		$originalSize = $this->size;

		$testImage = $this->image->coalesceImages();

		$frameCount = method_exists( $testImage, 'count' ) ? $testImage->count() : $testImage->getNumberImages();

		if ( $frameCount === 1 ) {
			return parent::multi_resize( $sizes );
		}

		foreach ( $sizes as $size => $data ) {
			if ( gattiny_ImageSizes::shouldNotResize( $size ) ) {
				continue;
			}

			$originalHeight = (int) $originalSize['height'];
			$newHeight      = (int) $data['height'];
			$originalWidth  = (int) $originalSize['width'];
			$newWidth       = (int) $data['width'];
			$crop           = (bool) $data['crop'];

			if ( ! gattiny_ImageSizes::shouldResizeAnimated( $size ) ) {
				$this->size = $originalSize;
				$resized    = parent::resize( $newWidth, $newHeight, $crop );

				if ( is_wp_error( $resized ) || ! $resized ) {
					continue;
				}

				$saved = parent::_save( $this->image );

				if ( is_wp_error( $saved ) ) {
					continue;
				}

				$metadata[ $size ] = $saved;
			}

			if ( ! ( image_resize_dimensions( $this->size['width'], $this->size['height'], $newWidth, $newHeight, $crop ) ) ) {
				return new WP_Error( 'error_getting_dimensions', __( 'Could not calculate resized image dimensions' ) );
			}

			$resized = $this->resize( $newWidth, $newHeight, $crop );

			$duplicate = ( ( $originalWidth == $newWidth ) && ( $originalHeight == $newHeight ) );

			if ( ! is_wp_error( $resized ) && ! $duplicate ) {
				$resized = $this->_save( $this->image );

				$this->image->clear();
				$this->image->destroy();
				$this->image = null;

				if ( ! is_wp_error( $resized ) && $resized ) {
					unset( $resized['path'] );
					$metadata[ $size ] = $resized;
				}
			}

			$this->size  = $originalSize;
			$this->image = $original;
		}


		return $metadata;
	}

	public function resize( $max_w, $max_h, $crop = false ) {
		$testImage = $this->image->coalesceImages();

		$frameCount = method_exists( $testImage, 'count' ) ? $testImage->count() : $testImage->getNumberImages();

		if ( $frameCount === 1 ) {
			return parent::resize( $max_w, $max_h, $crop );
		}

		try {
			$this->image = $testImage;

			do {
				if ( ! $crop ) {
					$this->image->resizeImage( $max_w, 0, Imagick::FILTER_BOX, 1, false );
					$this->update_size( $max_w, $this->image->getImageHeight() );
				} else {
					$this->image->cropThumbnailImage( $max_w, $max_h );
					$this->update_size( $max_w, $max_h );
				}
			} while ( $this->image->nextImage() );


			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'gattiny-resize-error', __( 'Gattiny generated an error: ', 'gattiny' ) . $e->getMessage() );
		}

	}

	public function _save( $image, $filename = null, $mime_type = null ) {
		$frameCount = method_exists( $this->image, 'count' ) ? $this->image->count() : $this->image->getNumberImages();

		if ( $frameCount === 1 ) {
			return parent::_save( $image, $filename, $mime_type );
		}

		try {
			$this->image = $this->image->deconstructImages();
			$filename    = $this->generate_filename( null, null, 'gif' );
			$this->image->writeImages( $filename, true );

			/** This filter is documented in wp-includes/class-wp-image-editor-gd.php */
			return array(
				'path'      => $filename,
				'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
				'width'     => $this->size['width'],
				'height'    => $this->size['height'],
				'mime-type' => $mime_type,
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'gattiny-save-error', __( 'Gattiny generated an error: ', 'gattiny' ) . $e->getMessage() );
		}

	}
}

<?php

include_once ABSPATH . '/wp-includes/class-wp-image-editor.php';
include_once ABSPATH . '/wp-includes/class-wp-image-editor-imagick.php';

class gattiny_GifEditor extends WP_Image_Editor_Imagick {

	public static function test( $args = [] ) {
		$mimeTypeIsGif      = ! empty( $args['mime_type'] ) && $args['mime_type'] === 'image/gif';
		$hasRequiredMethods = method_exists( 'Imagick', 'cropThumbnailImage' );

		return parent::test( $args ) && $mimeTypeIsGif && $hasRequiredMethods;
	}

	public function multi_resize( $sizes ) {
		$metadata     = [];
		$original     = $this->image;
		$originalSize = $this->size;

		/**
		 * Filters the upper bound that will be used to resize images.
		 *
		 * Resizing animated images is a resource intensive process so we set an upper bound
		 * so that images will be resized, while keeping the size ratio, to an image contained
		 * within those bounds. E.g. given an 800x600 original image, an upper bound of 300 (px) and
		 * a size of 1200x600 then the image will be resize to 300x150 (same 2:1 ratio as original).
		 * Cropping will follow the same principle but the cropped format ratio will be used.
		 *
		 * @param int $upperBound A pixel value.
		 */
		$upperBound = apply_filters( 'gattiny.editor.image-upper-bound', get_option( 'gattiny-image-upper-bound' ) );
		$upperBound = is_numeric($upperBound) && $upperBound > 0
			? (int)$upperBound
			: 600;

		$testImage = $this->image->coalesceImages();

		if ( $testImage->count() === 1 ) {
			return parent::multi_resize( $sizes );
		}

		foreach ( $sizes as $size => $data ) {
			$originalHeight = (int) $originalSize['height'];
			$newHeight      = (int) $data['height'];
			$originalWidth  = (int) $originalSize['width'];
			$newWidth       = (int) $data['width'];
			$crop = (bool)$data['crop'];

			if ( $newWidth > $upperBound || $newHeight > $upperBound ) {
				if ( $crop ) {
					$ratio = $newWidth / $newHeight;
				} else {
					$ratio = $originalWidth / $originalHeight;
				}

				if ( $ratio > 1 ) {
					// landscape
					$newWidth  = $upperBound;
					$newHeight = $upperBound / $ratio;
				} else {
					// portrait or square
					$newWidth  = $upperBound / $ratio;
					$newHeight = $upperBound;
				}
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

		if ( $testImage->count() === 1 ) {
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
		if ( $this->image->count() === 1 ) {
			return parent::_save( $image, $filename, $mime_type );
		}

		try {
			$this->image = $this->image->deconstructImages();
			$filename    = $this->generate_filename( null, null, 'gif' );
			$this->image->writeImages( $filename, true );

			/** This filter is documented in wp-includes/class-wp-image-editor-gd.php */
			return [
				'path'      => $filename,
				'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
				'width'     => $this->size['width'],
				'height'    => $this->size['height'],
				'mime-type' => $mime_type,
			];
		} catch ( Exception $e ) {
			return new WP_Error( 'gattiny-save-error', __( 'Gattiny generated an error: ', 'gattiny' ) . $e->getMessage() );
		}

	}
}

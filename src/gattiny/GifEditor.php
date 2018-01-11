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

		$testImage = $this->image->coalesceImages();

		if ( $testImage->count() === 1 ) {
			return parent::multi_resize( $sizes );
		}

		foreach ( $sizes as $size => $data ) {
			$originalHeight = $originalSize['height'];
			$newHeight      = $data['height'];
			$originalWidth  = $originalSize['width'];
			$newWidth       = $data['width'];

			if ( $originalHeight <= $newHeight || $originalWidth <= $newWidth ) {
				continue;
			}

			$resized = $this->resize( $newWidth, $newHeight, $data['crop'] );

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

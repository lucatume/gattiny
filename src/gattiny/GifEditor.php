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

			$this->size  = $originalSize;
			$this->image = $original;

			$sizeData = $this->generateSizeData( $size, $originalSize, $data );

			if ( ! gattiny_ImageSizes::shouldResizeAnimated( $size ) ) {
				$resized = $this->resizeAnimated( $sizeData );
			} else {
				$resized = $this->resizeStill( $sizeData );
			}

			if ( false !== $resized ) {
				$metadata[ $size ] = $resized;
			}
		}

		return $metadata;
	}

	protected function generateSizeData( $size, $originalSize, $data ) {
		$sizeData = array(
			'size'           => $size,
			'originalSize'   => $originalSize,
			'originalHeight' => (int) $originalSize['height'],
			'newHeight'      => (int) $data['height'],
			'originalWidth'  => (int) $originalSize['width'],
			'newWidth'       => (int) $data['width'],
			'crop'           => (bool) $data['crop'],
		);

		return $sizeData;
	}

	protected function resizeAnimated( array $data ) {
		$this->size = $data['originalSize'];
		$resized    = parent::resize( $data['newWidth'], $data['newHeight'], $data['crop'] );

		if ( is_wp_error( $resized ) || ! $resized ) {
			return false;
		}

		$saved = parent::_save( $this->image );

		if ( is_wp_error( $saved ) ) {
			return false;
		}

		return $saved;
	}

	protected function resizeStill( array $data ) {
		if ( ! image_resize_dimensions( $this->size['width'], $this->size['height'], $data['newWidth'], $data['newHeight'], $data['crop'] ) ) {
			return false;
		}

		$resized = $this->resize( $data['newWidth'], $data['newHeight'], $data['crop'] );

		$duplicate = ( ( $data['originalWidth'] == $data['newWidth'] ) && ( $data['originalHeight'] == $data['newHeight'] ) );

		if ( ! is_wp_error( $resized ) && ! $duplicate ) {
			$resized = $this->_save( $this->image );

			$this->image->clear();
			$this->image->destroy();
			$this->image = null;

			if ( is_wp_error( $resized ) || ! $resized ) {
				return false;
			}

			unset( $resized['path'] );

			return $resized;
		}
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

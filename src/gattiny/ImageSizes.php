<?php

class gattiny_ImageSizes {

	const OPTION = 'gattiny-imageSizes';

	const DO_NOT_CONVERT = 'do-not-convert';

	const CONVERT_ANIMATED = 'convert-animated';

	const CONVERT_STILL = 'convert-still';

	/**
	 * @var array
	 */
	protected $imageSizes;

	public static function shouldNotResize( $size ) {
		$option = get_option( self::OPTION, array() );

		return ( isset( $option[ $size ] ) && $option[ $size ] === self::DO_NOT_CONVERT );
	}

	public static function shouldResizeAnimated( $size ) {
		$option = get_option( self::OPTION, array() );

		return ( isset( $option[ $size ] ) && $option[ $size ] === self::CONVERT_ANIMATED )
		       || ! isset( $option[ $size ] );
	}

	public function getDefaultConversionFor( $imageSize ) {
		$sizes = $this->getSizes();

		if ( empty( $sizes ) || empty( $sizes[ $imageSize ] ) ) {
			return self::CONVERT_STILL;
		}

		$size = $sizes[ $imageSize ];

		if ( $size->getConversionCost() > self::getMediumThreshold() ) {
			return self::CONVERT_STILL;
		}

		return self::CONVERT_ANIMATED;
	}

	/**
	 * @return gattiny_ImageSize[]
	 */
	public function getSizes() {
		$this->initImageSizes();

		return $this->imageSizes;
	}

	protected function initImageSizes() {
		if ( is_array( $this->imageSizes ) ) {
			return;
		}

		$this->imageSizes = array();

		$imageSizes = $this->getImageSizes();

		foreach ( $imageSizes as $name => $data ) {
			$this->imageSizes[ $name ] = new gattiny_ImageSize(
				$name,
				$this->getImageWidth( $name ),
				$this->getImageHeight( $name ),
				$this->isImageCropping( $name )
			);
		}
	}

	protected function getImageSizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $size ) {
			if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $size ]['width']  = get_option( "{$size}_size_w" );
				$sizes[ $size ]['height'] = get_option( "{$size}_size_h" );
				$sizes[ $size ]['crop']   = (bool) get_option( "{$size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$sizes[ $size ] = array(
					'width'  => $_wp_additional_image_sizes[ $size ]['width'],
					'height' => $_wp_additional_image_sizes[ $size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
				);
			}
		}

		return $sizes;
	}

	public function getImageWidth( $size ) {
		if ( ! $size = $this->getImageSize( $size ) ) {
			return false;
		}

		if ( isset( $size['width'] ) ) {
			return $size['width'];
		}

		return false;
	}

	protected function getImageSize( $size ) {
		$sizes = $this->getImageSizes();

		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		}

		return false;
	}

	public function getImageHeight( $size ) {
		if ( ! $size = $this->getImageSize( $size ) ) {
			return false;
		}

		if ( isset( $size['height'] ) ) {
			return $size['height'];
		}

		return false;
	}

	public function isImageCropping( $size ) {
		if ( ! $size = $this->getImageSize( $size ) ) {
			return false;
		}

		if ( isset( $size['crop'] ) ) {
			return $size['crop'];
		}

		return false;
	}

	public function getMediumThreshold() {
		return 600 * 600;
	}

	public function getLowThreshold() {
		return 200 * 200;
	}
}
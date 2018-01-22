<?php

class gattiny_ImageSize {

	/**
	 * @var int
	 */
	protected $height;
	/**
	 * @var bool
	 */
	protected $isCropping;
	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @var int
	 */
	protected $width;

	public function __construct( $name, $width, $height, $isCropping = false ) {
		if ( ! is_string( $name ) ) {
			throw new InvalidArgumentException( 'Image name should be a string' );
		}

		if ( ! ( is_numeric( $width ) && (int) $width >= 0 && is_numeric( $height ) && (int) $height >= 0 ) ) {
			throw new InvalidArgumentException( 'Width and height should be numbers greater or equal to 0' );
		}

		$this->name       = $name;
		$this->width      = (int) $width;
		$this->height     = (int) $height;
		$this->isCropping = (bool) $isCropping;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * @return int
	 */
	public function getHeight() {
		return $this->height;
	}

	/**
	 * return int
	 */
	public function getConversionCost() {
		$width  = $this->width;
		$height = $this->height;

		if ( $this->width === 0 ) {
			$width = $this->height / 2;
		} elseif ( $this->height === 0 ) {
			$height = $this->width / 2;
		}

		return $height * $width;
	}

	public function isCropping() {
		return $this->isCropping;
	}
}
<?php

namespace gattiny;

use gattiny_ImageSize as ImageSize;
use gattiny_ImageSizes as ImageSizes;
use function tad\FunctionMockerLe\define as defineFunction;

class ImageSizesTest extends \Codeception\Test\Unit {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * It should return expected image sizes
	 *
	 * @test
	 */
	public function should_return_expected_image_sizes() {
		global $_wp_additional_image_sizes;
		$_wp_additional_image_sizes = [
			'foo' => [
				'width'  => 100,
				'height' => 200,
				'crop'   => false,
			],
			'baz' => [
				'width'  => 200,
				'height' => 300,
				'crop'   => true,
			],
		];
		defineFunction( 'get_intermediate_image_sizes', function () {
			return [ 'foo', 'baz', 'thumbnail' ];
		} );
		defineFunction( 'get_option', function ( $option ) {
			$map = [
				'thumbnail_size_w' => 150,
				'thumbnail_size_h' => 100,
				'thumbnail_crop'   => true,
			];

			if ( ! isset( $map[ $option ] ) ) {
				throw new \InvalidArgumentException( "Option {$option} is not mapped" );
			}

			return $map[ $option ];
		} );

		$imageSizes = ( new ImageSizes() )->getSizes();

		$this->assertCount( 3, $imageSizes );
		$this->assertContainsOnlyInstancesOf( ImageSize::class, $imageSizes );
		foreach ( $_wp_additional_image_sizes as $k => $i ) {
			$this->assertEquals( $i['width'], $imageSizes[ $k ]->getWidth() );
			$this->assertEquals( $i['height'], $imageSizes[ $k ]->getHeight() );
			$this->assertEquals( $i['crop'], $imageSizes[ $k ]->isCropping() );
		}
		$this->assertArrayHasKey( 'thumbnail', $imageSizes );
		$this->assertEquals( 150, $imageSizes['thumbnail']->getWidth() );
		$this->assertEquals( 100, $imageSizes['thumbnail']->getHeight() );
		$this->assertEquals( true, $imageSizes['thumbnail']->isCropping() );
	}

	/**
	 * It should correctly return height and width of default image sizes
	 *
	 * @test
	 */
	public function should_correctly_return_height_and_width_of_default_image_sizes() {
		defineFunction( 'get_intermediate_image_sizes', function () {
			return [ 'foo', 'baz', 'thumbnail' ];
		} );
		defineFunction( 'get_option', function ( $option ) {
			$map = [
				'thumbnail_size_w' => 150,
				'thumbnail_size_h' => 100,
				'thumbnail_crop'   => true,
			];

			if ( ! isset( $map[ $option ] ) ) {
				throw new \InvalidArgumentException( "Option {$option} is not mapped" );
			}

			return $map[ $option ];
		} );

		$imageSizes = new ImageSizes();

		$this->assertEquals( 150, $imageSizes->getImageWidth( 'thumbnail' ) );
		$this->assertEquals( 100, $imageSizes->getImageHeight( 'thumbnail' ) );
		$this->assertEquals( true, $imageSizes->isImageCropping( 'thumbnail' ) );
	}

	/**
	 * It should correctly return height and width of additional image sizes
	 *
	 * @test
	 */
	public function should_correctly_return_height_and_width_of_additional_image_sizes() {
		global $_wp_additional_image_sizes;
		$_wp_additional_image_sizes = [
			'foo' => [
				'width'  => 100,
				'height' => 200,
				'crop'   => false,
			],
			'baz' => [
				'width'  => 200,
				'height' => 300,
				'crop'   => true,
			],
		];
		defineFunction( 'get_intermediate_image_sizes', function () {
			return [ 'foo', 'baz', 'thumbnail' ];
		} );
		defineFunction( 'get_option', function ( $option ) {
			$map = [
				'thumbnail_size_w' => 150,
				'thumbnail_size_h' => 100,
				'thumbnail_crop'   => true,
			];

			if ( ! isset( $map[ $option ] ) ) {
				throw new \InvalidArgumentException( "Option {$option} is not mapped" );
			}

			return $map[ $option ];
		} );

		$imageSizes = new ImageSizes();

		foreach ( $_wp_additional_image_sizes as $k => $i ) {
			$this->assertEquals( $i['width'], $imageSizes->getImageWidth( $k ) );
			$this->assertEquals( $i['height'], $imageSizes->getImageHeight( $k ) );
			$this->assertEquals( $i['crop'], $imageSizes->isImageCropping( $k ) );
		}
		$this->assertEquals( false, $imageSizes->getImageWidth( 'not-existing' ) );
		$this->assertEquals( false, $imageSizes->getImageHeight( 'not-existing' ) );
		$this->assertEquals( false, $imageSizes->isImageCropping( 'not-existing' ) );
	}

	/**
	 * It should return correct value to know if image should be resized
	 *
	 * @test
	 */
	public function should_return_correct_value_to_know_if_image_should_be_resized() {
		defineFunction( 'get_option', function () {
			return [ 'size' => 'do-not-convert' ];
		} );

		$this->assertTrue( ImageSizes::shouldNotResize( 'size' ) );
		$this->assertFalse( ImageSizes::shouldResizeAnimated( 'size' ) );

		defineFunction( 'get_option', function () {
			return [];
		} );

		$this->assertFalse( ImageSizes::shouldNotResize( 'size' ) );
		$this->assertTrue( ImageSizes::shouldResizeAnimated( 'size' ) );

		defineFunction( 'get_option', function () {
			return [ 'size' => 'convert-animated' ];
		} );

		$this->assertFalse( ImageSizes::shouldNotResize( 'size' ) );
		$this->assertTrue( ImageSizes::shouldResizeAnimated( 'size' ) );

		defineFunction( 'get_option', function () {
			return [ 'size' => 'convert-still' ];
		} );

		$this->assertFalse( ImageSizes::shouldNotResize( 'size' ) );
		$this->assertFalse( ImageSizes::shouldResizeAnimated( 'size' ) );
	}

	/**
	 * It should return the correct default conversion option for image sizes
	 *
	 * @test
	 */
	public function should_return_the_correct_default_conversion_option_for_image_sizes() {
		global $_wp_additional_image_sizes;
		$_wp_additional_image_sizes = [
			'small-format'  => [
				'width'  => 200,
				'height' => 200,
				'crop'   => false,
			],
			'medium-format' => [
				'width'  => 500,
				'height' => 500,
				'crop'   => true,
			],
			'large-format'  => [
				'width'  => 800,
				'height' => 800,
				'crop'   => true,
			],
		];
		defineFunction( 'get_intermediate_image_sizes', function () {
			return [ 'small-format', 'medium-format', 'large-format', 'thumbnail' ];
		} );
		defineFunction( 'get_option', function ( $option ) {
			$map = [
				'thumbnail_size_w' => 150,
				'thumbnail_size_h' => 100,
				'thumbnail_crop'   => true,
			];

			if ( ! isset( $map[ $option ] ) ) {
				throw new \InvalidArgumentException( "Option {$option} is not mapped" );
			}

			return $map[ $option ];
		} );

		$sut = new ImageSizes();

		$this->assertEquals( ImageSizes::CONVERT_ANIMATED, $sut->getDefaultConversionFor( 'small-format' ) );
		$this->assertEquals( ImageSizes::CONVERT_ANIMATED, $sut->getDefaultConversionFor( 'medium-format' ) );
		$this->assertEquals( ImageSizes::CONVERT_STILL, $sut->getDefaultConversionFor( 'large-format' ) );
		$this->assertEquals( ImageSizes::CONVERT_ANIMATED, $sut->getDefaultConversionFor( 'thumbnail' ) );
	}
}

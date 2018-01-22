<?php

namespace gattiny;

use gattiny_ImageSize as ImageSize;

class ImageSizeTest extends \Codeception\Test\Unit {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * It should throw exception on bad construction parameters
	 *
	 * @test
	 */
	public function should_throw_exception_on_bad_construction_parameters() {
		$this->expectException( \InvalidArgumentException::class );

		new ImageSize( 23, 100, 200, false );

		$this->expectException( \InvalidArgumentException::class );

		new ImageSize( 'foo', 'bar', 200, false );

		$this->expectException( \InvalidArgumentException::class );

		new ImageSize( 'foo', 100, 'bar', false );
	}

	public function conversionCosts() {
		return [
			[ 100, 100, false, 100 * 100 ],
			[ 100, 0, false, 100 * 50 ],
			[ 0, 100, false, 100 * 50 ],
			[ 100, 100, true, 100 * 100 ],
			[ 100, 0, true, 100 * 50 ],
			[ 0, 100, true, 100 * 50 ],
		];
	}

	/**
	 * It should return expected conversion costs
	 *
	 * @dataProvider conversionCosts
	 *
	 * @test
	 */
	public function should_return_expected_conversion_costs( $w, $h, $crop, $expected ) {
		$this->assertEquals( $expected, ( new ImageSize( 'image-size', $w, $h, $crop ) )->getConversionCost() );
	}
}
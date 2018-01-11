<?php


use Codeception\Exception\ModuleException;

class FormatCreationCest {

	protected $gif = 'images/medium.gif';

	protected $frameCount = 2;

	protected $uploads;

	protected $sizeMap = [
		'custom-format-one'  => '100x80',
		'custom-format-two ' => '133x75',
		'thumbnail'          => '150x150',
	];

	public function _before( FunctionalTester $I ) {
		$this->uploads = getenv( 'WP_UPLOADS_FOLDER' ) . '/' . date( 'Y/m' );
		$I->deleteDir( $this->uploads );

		$functionsCode = <<< PHP
add_image_size( 'custom-format-one', 100, 80, true );
add_image_size( 'custom-format-two', 133, 200, false );

add_filter( 'intermediate_image_sizes_advanced', 'testRemoveDefaultSizes' );
function testRemoveDefaultSizes( array \$sizes ) {
	unset( \$sizes['medium_large']);
	unset( \$sizes['large']);
 
	return \$sizes;
}
PHP;

		try {
			$I->haveTheme( 'test', "echo 'Hello there!';", $functionsCode );
		} catch ( ModuleException $e ) {
			$I->fail( "It was not possible to have test theme; issue is {$e->getMessage()}" );
		}
		$I->useTheme( 'test' );
	}

	public function _after( FunctionalTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	public function _failed( FunctionalTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	/**
	 * It should create a version of the gif for each size
	 *
	 * @test
	 */
	public function create_a_version_of_the_gif_for_each_size( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $this->gif );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$I->amInPath( $this->uploads );
		foreach ( $this->sizeMap as $slug => $size ) {
			$suffix = '' !== $size ? '-' . $size : '';
			$I->seeFileFound( basename( $this->gif, '.gif' ) . $suffix . '.gif', $this->uploads );
		}
	}

	/**
	 * It should create an animated resized version of each animated GIF image
	 *
	 * @test
	 */
	public function create_an_animated_resized_version_of_each_animated_gif_image( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $this->gif );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$I->amInPath( $this->uploads );
		foreach ( $this->sizeMap as $slug => $size ) {
			$suffix = '' !== $size ? '-' . $size : '';
			$file   = $this->uploads . '/' . basename( $this->gif, '.gif' ) . $suffix . '.gif';
			$image  = ( new Imagick( $file ) )->coalesceImages();
			$I->assertEquals( $this->frameCount, $image->count() );
		}
	}

	/**
	 * It should create resized version that are still the same image
	 *
	 * @test
	 */
	public function create_resized_version_that_are_still_the_same_image( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $this->gif );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$originalCoalesced = ( new Imagick( codecept_data_dir( $this->gif ) ) )->coalesceImages();

		$I->amInPath( $this->uploads );
		foreach ( $this->sizeMap as $slug => $size ) {
			if ( $slug === 'full' ) {
				continue;
			}

			$isCroppingFormat = in_array( $slug, [ 'thumbnail', 'custom-format-one' ] );

			$suffix           = '' !== $size ? '-' . $size : '';
			$file             = $this->uploads . DIRECTORY_SEPARATOR . basename( $this->gif, '.gif' ) . $suffix . '.gif';
			$resizedCoalesced = ( new Imagick( $file ) )->coalesceImages();
			list( $w, $h ) = explode( 'x', $size );
			// we test just the first frame
			$originalFrame = $originalCoalesced->getImage();
			$resizedFrame  = $resizedCoalesced->getImage();

			if ( $isCroppingFormat ) {
				try {
					$originalFrame->cropThumbnailImage( $w, $h );
				} catch ( ImagickException $e ) {
					$I->fail( "Imagick failed to crop the images for size '{$slug}'; the issue was {$e->getMessage()}" );
				}
			} else {
				$originalFrame->resizeImage( $w, $h, Imagick::FILTER_BOX, 1 );
			}

			try {
				$comparison = $originalFrame->compareImages( $resizedFrame, Imagick::METRIC_ROOTMEANSQUAREDERROR );
			} catch ( ImagickException $e ) {
				$I->fail( "Imagick failed to compare the images for size '{$slug}'; the issue was {$e->getMessage()}" );
			}
			$I->assertTrue( 0 <= $comparison[1] && $comparison[1] <= 0.5, "The {$slug} format image is not comparable" );
		}
	}
}

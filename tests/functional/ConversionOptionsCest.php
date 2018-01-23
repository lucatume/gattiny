<?php


use Codeception\Exception\ModuleException;
use gattiny_ImageSizes as ImageSizes;

class ConversionOptionsCest {

	protected $uploads;

	public function _before( FunctionalTester $I ) {
		$this->uploads = getenv( 'WP_UPLOADS_FOLDER' ) . '/' . date( 'Y/m' );
		$I->deleteDir( $this->uploads );
	}

	public function _after( FunctionalTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	public function _failed( FunctionalTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	/**
	 * It should convert images keeping animations if option for image size is not set
	 *
	 * @test
	 */
	public function should_convert_images_keeping_animations_if_option_for_image_size_is_not_set( FunctionalTester $I ) {
		$functionsCode = <<< PHP
add_image_size( 'smaller-not-cropping', 200, 200, false );
add_image_size( 'smaller-cropping', 100, 100, true );
add_image_size( 'larger-cropping', 600, 600, true );

add_filter( 'intermediate_image_sizes_advanced', 'testRemoveDefaultSizes' );
function testRemoveDefaultSizes( array \$sizes ) {
	unset( \$sizes['thumbnail']);
	unset( \$sizes['medium']);
	unset( \$sizes['medium_large']);
	unset( \$sizes['large']);
 
	return \$sizes;
}
PHP;

		$id        = uniqid( 'test', true );
		$themeName = "test-theme-{$id}";
		$I->useTheme( $themeName );
		$image = 'images/medium.gif'; // 500 x 281

		try {
			$I->haveTheme( $themeName, "echo 'Hello there!';", $functionsCode );
		} catch ( ModuleException $e ) {
			$I->fail( "It was not possible to have test theme; issue is {$e->getMessage()}" );
		}
		$I->useTheme( $themeName );

		$I->haveOptionInDatabase( 'gattiny-image-upper-bound', '300' );

		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $image );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$I->amInPath( $this->uploads );
		$I->seeFileFound( 'medium-200x112' . '.gif', $this->uploads );
		$I->seeFileFound( 'medium-100x100' . '.gif', $this->uploads );
		$I->dontSeeFileFound( 'medium-600x600' . '.gif', $this->uploads );
		$I->seeFileFound( 'medium.gif', $this->uploads );
		$I->assertCount( 3, glob( $this->uploads . '/medium*.gif' ) );
	}

	/**
	 * It should not create converted versions according to options
	 *
	 * @test
	 */
	public function should_not_create_converted_versions_according_to_options( FunctionalTester $I ) {
		$option = ImageSizes::OPTION;
		$I->haveOptionInDatabase( $option, [
			'smaller-not-cropping' => ImageSizes::DO_NOT_CONVERT,
			'smaller-cropping'     => ImageSizes::CONVERT_ANIMATED,
			'almost-cropping'      => ImageSizes::CONVERT_STILL,
		] );

		$functionsCode = <<< PHP
add_image_size( 'smaller-not-cropping', 200, 200, false );
add_image_size( 'smaller-cropping', 100, 100, true );
add_image_size( 'almost-cropping', 500, 200, true );

add_filter( 'intermediate_image_sizes_advanced', 'testRemoveDefaultSizes' );
function testRemoveDefaultSizes( array \$sizes ) {
	unset( \$sizes['thumbnail']);
	unset( \$sizes['medium']);
	unset( \$sizes['medium_large']);
	unset( \$sizes['large']);
 
	return \$sizes;
}
PHP;

		$id        = uniqid( 'test', true );
		$themeName = "test-theme-{$id}";
		$I->useTheme( $themeName );
		$image = 'images/medium.gif'; // 500 x 281

		try {
			$I->haveTheme( $themeName, "echo 'Hello there!';", $functionsCode );
		} catch ( ModuleException $e ) {
			$I->fail( "It was not possible to have test theme; issue is {$e->getMessage()}" );
		}
		$I->useTheme( $themeName );

		$I->haveOptionInDatabase( 'gattiny-image-upper-bound', '300' );

		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $image );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$I->amInPath( $this->uploads );
		$I->dontSeeFileFound( 'medium-200x112.gif', $this->uploads );
		$I->seeFileFound( 'medium-100x100.gif', $this->uploads );
		$images = ( new Imagick( $this->uploads . '/medium-100x100.gif' ) )->coalesceImages();
		$I->assertEquals( 2, $images->count() );
		$I->seeFileFound( 'medium-500x200.gif', $this->uploads );
		$images = ( new Imagick( $this->uploads . '/medium-500x200.gif' ) )->coalesceImages();
		$I->assertEquals( 1, $images->count() );
		$I->seeFileFound( 'medium.gif', $this->uploads );
		$I->assertCount( 3, glob( $this->uploads . '/medium*.gif' ) );
	}
}

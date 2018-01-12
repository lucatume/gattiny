<?php


use Codeception\Exception\ModuleException;

class LargerFormatCest {

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
	 * It should create upper bound images keeping ratio
	 *
	 * @test
	 */
	public function should_create_upper_bound_images_keeping_ratio(FunctionalTester $I) {
		$functionsCode = <<< PHP
add_image_size( 'smaller-not-cropping', 200, 200, false );
add_image_size( 'smaller-cropping', 200, 200, true );
add_image_size( 'larger-not-cropping', 1000, 1000, false );
add_image_size( 'larger-cropping', 1000, 1000, true );

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
		$image = 'images/large.gif';

		try {
			$I->haveTheme( $themeName, "echo 'Hello there!';", $functionsCode );
		} catch ( ModuleException $e ) {
			$I->fail( "It was not possible to have test theme; issue is {$e->getMessage()}" );
		}
		$I->useTheme( $themeName );

		$I->haveOptionInDatabase('gattiny-image-upper-bound', '300');

		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $image );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$I->amInPath( $this->uploads );
		$I->seeFileFound( basename( $image, '.gif' ) . '-200x125' . '.gif', $this->uploads );
		$I->seeFileFound( basename( $image, '.gif' ) . '-200x200' . '.gif', $this->uploads );
		$I->seeFileFound( basename( $image, '.gif' ) . '-300x188' . '.gif', $this->uploads );
		$I->seeFileFound( basename( $image, '.gif' ) . '-300x300' . '.gif', $this->uploads );
		$I->seeFileFound( 'large.gif', $this->uploads );
	}
}

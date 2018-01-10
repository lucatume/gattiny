<?php


use Codeception\Exception\ModuleException;

class LargerFormatCest {

	protected $gif = 'images/small.gif';
	protected $uploads;

	public function _before( FunctionalTester $I ) {
		$this->uploads = getenv( 'WP_UPLOADS_FOLDER' ) . '/' . date( 'Y/m' );
		$I->deleteDir( $this->uploads );

		$functionsCode = <<< PHP
add_image_size( 'smaller-not-cropping', 50, 50, false );
add_image_size( 'smaller-cropping', 55, 55, true );
add_image_size( 'larger-not-cropping', 65, 65, false );
add_image_size( 'larger-cropping', 70, 70, true );

add_filter( 'intermediate_image_sizes_advanced', 'testRemoveDefaultSizes' );
function testRemoveDefaultSizes( array \$sizes ) {
	unset( \$sizes['thumbnail']);
	unset( \$sizes['medium']);
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
	 * It should not create larger versions of the image
	 *
	 * @test
	 */
	public function should_not_create_larger_versions_of_the_image( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'media-new.php' );
		$I->attachFile( 'input[name="async-upload"]', $this->gif );
		$I->click( 'input[name="html-upload"]' );

		$I->seeResponseCodeIs( 200 );

		$I->amInPath( $this->uploads );
		$I->seeFileFound( basename( $this->gif, '.gif' ) . '-50x50' . '.gif', $this->uploads );
		$I->seeFileFound( basename( $this->gif, '.gif' ) . '-55x55' . '.gif', $this->uploads );
		$I->dontSeeFileFound( basename( $this->gif, '.gif' ) . '-65x65' . '.gif', $this->uploads );
		$I->dontSeeFileFound( basename( $this->gif, '.gif' ) . '-70x70' . '.gif', $this->uploads );
	}
}

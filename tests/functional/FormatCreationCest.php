<?php


class FormatCreationCest {
	protected $gif = 'images/kitten-animated.gif';
	protected $frameCount = 2;
	protected $jpg = 'images/kitten-image.jpg';
	protected $uploads;
	protected $sizeMap = [
		'thumbnail'    => '150x150',
		'medium'       => '300x169',
		'medium_large' => '768x432',
		'large'        => '1024x576',
		'full'         => ''
	];

	public function _before( FunctionalTester $I ) {
		$config        = \Codeception\Configuration::config();
		$this->uploads = $config['folders']['uploads'] . '/' . date( 'Y/m' );
		$I->useTheme( 'empty' );
	}

	public function _after( FunctionalTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	public function _failed( FunctionalTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	/**
	 * It should create a version of the gif for each size
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
			$I->seeFileFound( basename( $this->gif, '.gif' ) . $suffix . '.gif' );
		}
	}

	/**
	 * It should create an animated resized version of each animated GIF image
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
			$file   = $this->uploads . DIRECTORY_SEPARATOR . basename( $this->gif, '.gif' ) . $suffix . '.gif';
			$image  = ( new Imagick( $file ) )->coalesceImages();
			$I->assertEquals( $this->frameCount, $image->count() );
		}
	}
}

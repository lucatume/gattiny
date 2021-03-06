<?php


class GifImageEditingCest {

	protected $uploads;
	protected $gif = 'images/small.gif';

	public function _before( JsTester $I ) {
		$this->uploads = getenv( 'WP_UPLOADS_FOLDER' ) . '/' . date( 'Y/m' );
		$I->deleteDir( $this->uploads );
	}

	public function _after( JsTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	public function _failed( JsTester $I ) {
		$I->deleteDir( $this->uploads );
	}

	/**
	 * It should not allow editing GIF images in WordPress
	 *
	 * @test
	 */
	public function should_not_allow_editing_gif_images_in_word_press( JsTester $I ) {
		$I->haveAttachmentInDatabase( codecept_data_dir( $this->gif ),'yesterday',[],['thumbnail' => [70,70]]);
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/upload.php' );
		$I->click( '.thumbnail img[src$=".gif"]' );


		$I->seeElement( '.edit-attachment-frame' );
		$I->seeElement( '.edit-attachment-frame .attachment-actions' );
		$I->dontSeeElement( '.edit-attachment-frame .attachment-actions .edit-attachment' );
	}
}

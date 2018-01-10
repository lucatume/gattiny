<?php

class FiltersTest extends \Codeception\TestCase\WPTestCase {

	protected $toRemove = [];

	/**
	 * It should allow filtering the image_make_intermediate_size filter
	 *
	 * @test
	 */
	public function it_should_allow_filtering_the_image_make_intermediate_size_filter() {
		$filtered = [];
		add_filter( 'image_make_intermediate_size', function ( $filename ) use ( &$filtered ) {
			$filtered[] = $filename;

			return $filename;
		} );

		$editor = new gattiny_GifEditor( codecept_data_dir( 'images/medium.gif' ) );
		$editor->load();
		$saved = $editor->save();
		if ( is_array( $saved ) && ! empty( $saved['path'] ) ) {
			$this->toRemove[] = $saved['path'];
		}

		$this->assertNotEmpty( $filtered );
		$this->assertCount( 1, $filtered );
		foreach ( $filtered as $filename ) {
			$this->assertRegExp( '/^.*?medium.*?\.gif/', $filename );
		}
	}

	/**
	 * It should not filter the image editors if the plugin is not supported
	 *
	 * @test
	 */
	public function it_should_not_filter_the_image_editors_if_the_plugin_is_not_supported() {
		update_option( 'gattiny_supported', '0' );

		$imageEditors = new gattiny_ImageEditors();
		$filtered     = $imageEditors->filterImageEditors( [] );

		$this->assertNotContains( 'gattiny_GifEditor', $filtered );
	}

	protected function _after() {
		foreach ( $this->toRemove as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}
}
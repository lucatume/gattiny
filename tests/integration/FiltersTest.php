<?php

class FiltersTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * It should allow filtering the image_make_intermediate_size filter
	 *
	 * @test
	 */
	public function it_should_allow_filtering_the_image_make_intermediate_size_filter() {
		$filtered = [];
		add_filter('image_make_intermediate_size', function ($filename) use (&$filtered) {
			$filtered[] = $filename;

			return $filename;
		});

		$editor = new gattiny_GifEditor(codecept_data_dir('images/kitten-animated.gif'));
		$editor->load();
		$editor->save();

		$this->assertNotEmpty($filtered);
		$this->assertCount(1,$filtered);
		foreach ($filtered as $filename) {
			$this->assertRegExp('/^.*?kitten-animated.*?\.gif/', $filename);
		}
	}
}
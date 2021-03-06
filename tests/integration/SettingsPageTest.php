<?php

namespace gattiny;

use gattiny_SettingsPage as SettingsPage;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WPBrowser\Snapshot\WPHtmlOutputDriver;

class SettingsPageTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;

	/**
	 * Test snapshot for render
	 */
	public function test_snapshot_render() {
		global $gattinyServiceLocator;
		$sut = $gattinyServiceLocator->make( SettingsPage::class );
		$sut->addAdminMenu();
		$sut->initSettings();

		$out = $sut->render( false );

		$this->assertMatchesSnapshot( $out, new WPHtmlOutputDriver( getenv( 'WP_URL' ) ) );
	}
}
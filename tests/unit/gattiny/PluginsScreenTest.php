<?php

namespace gattiny;

use gattiny_PluginsScreen as PluginsScreen;
use Spatie\Snapshots\MatchesSnapshots;

class PluginsScreenTest extends \Codeception\Test\Unit {

	use MatchesSnapshots;
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Test snapshot addActionLinks
	 */
	public function test_snapshot_add_action_links() {
		\tad\FunctionMockerLe\define( 'esc_html__', function ( $in ) {
			return $in;
		} );

		$sut = new PluginsScreen();

		$this->assertMatchesSnapshot( $sut->addActionLinks( [ 'deactivate' => '<a href="plugins.php">Deactivate</a>' ] ) );
	}
}

<?php

namespace gattiny\TestDrivers;

use PHPUnit\Framework\Assert;
use Spatie\Snapshots\Drivers\VarDriver;

class WPOutput extends VarDriver {

	/**
	 * Match an expectation with a snapshot's actual contents. Should throw an
	 * `ExpectationFailedException` if it doesn't match. This happens by
	 * default if you're using PHPUnit's `Assert` class for the match.
	 *
	 * @param mixed $expected
	 * @param mixed $actual
	 *
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 */
	public function match( $expected, $actual ) {
		$evaluated = eval( substr( $expected, strlen( '<?php ' ) ) );

		Assert::assertEquals( $this->removeTimeValues( $evaluated ), $this->removeTimeValues( $actual ) );
	}

	protected function removeTimeValues( string $evaluated ): string {
		// remove nonce and other time-dependant values values to remove the time dependency
		$doc = \phpQuery::newDocument( $evaluated );

		foreach ( [ '_wpnonce' ] as $name ) {
			$doc->find( "#{$name}" )->each( function ( \DOMElement $t ) {
				$t->setAttribute( 'value', '' );
			} );
		}

		return $this->normalize( $doc->__toString() );
	}

	protected function normalize( string $input ): string {
		return preg_replace( '/\\s{2,}/', '', $input );
	}
}
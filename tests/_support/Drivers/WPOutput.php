<?php

namespace gattiny\TestDrivers;

use PHPUnit\Framework\Assert;
use Spatie\Snapshots\Drivers\VarDriver;

class WPOutput extends VarDriver {

	protected $url;

	public function __construct( $url ) {
		$this->url = $url;
	}

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

		Assert::assertEquals( $this->normalize( $evaluated ), $this->normalize( $actual ) );
	}

	protected function normalize( string $input ): string {
		$output = $this->replaceUrls( $input );
		$output = $this->removeTimeValues( $output );
		$output = $this->squashSpaces( $output );

		return $output;
	}

	protected function replaceUrls( string $input ): string {
		$doc = \phpQuery::newDocument( $input );

		foreach ( [ 'href', 'src' ] as $name ) {
			$doc->find( "*[{$name}]" )->each( function ( \DOMElement $t ) use ( $name ) {
				$current     = $t->getAttribute( $name );
				$snapshotUrl = sprintf( '%s://%s',
					parse_url( $current, PHP_URL_SCHEME ),
					parse_url( $current, PHP_URL_HOST )
				);

				if ( $port = parse_url( $current, PHP_URL_PORT ) ) {
					$snapshotUrl .= ":{$port}";
				}

				$t->setAttribute( $name, str_replace( $snapshotUrl, $this->url, $current ) );
			} );
		}

		return $this->squashSpaces( $doc->__toString() );
	}

	protected function squashSpaces( string $input ): string {
		return preg_replace( '/\\s{2,}/', '', $input );
	}

	protected function removeTimeValues( string $input ): string {
		// remove nonce and other time-dependant values values to remove the time dependency
		$doc = \phpQuery::newDocument( $input );

		foreach ( [ '_wpnonce' ] as $name ) {
			$doc->find( "#{$name}" )->each( function ( \DOMElement $t ) {
				$t->setAttribute( 'value', '' );
			} );
		}

		return $this->squashSpaces( $doc->__toString() );
	}
}
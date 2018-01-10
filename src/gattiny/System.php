<?php

/**
 * Class gattiny_System
 *
 * Handles the plugin interaction with the system and WordPress installation.
 */
class gattiny_System {

	/**
	 * @var string The path to the plugin main file.
	 */
	protected $mainFile;

	public function __construct() {
		$this->mainFile = dirname( dirname( dirname( __FILE__ ) ) ) . '/gattiny.php';
	}

	public function maybeDeactivate() {
		if ( '0' === get_option( 'gattiny_supported' ) || ! extension_loaded( 'imagick' ) ) {
			unset( $_GET['activate'] );
			add_action( 'admin_notices', array( $this, 'unsupportedNotice' ) );
		}
	}

	public function unsupportedNotice() {
		deactivate_plugins( plugin_basename( $this->mainFile ) );
		?>
        <div class="notice notice-error gattiny_Notice gattiny_Notice--unsupported">
            <p><?php _e( 'Gattiny is not supported by your server: the Imagick extension is missing.', 'gattiny' ); ?></p>
        </div>
		<?php
	}
}

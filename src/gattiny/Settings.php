<?php

class gattiny_Settings extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public function register() {
		$this->container->singleton( 'gattiny_SettingsPage', 'gattiny_SettingsPage' );

		add_action( 'admin_menu', $this->container->callback( 'gattiny_SettingsPage', 'addAdminMenu' ) );
		add_action( 'admin_init', $this->container->callback( 'gattiny_SettingsPage', 'initSettings' ) );
	}
}

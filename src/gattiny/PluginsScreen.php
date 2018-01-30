<?php

class gattiny_PluginsScreen {

	public function addActionLinks( array $actionLinks = array() ) {
		$actionLinks['settings'] = sprintf(
			'<a href="options-general.php?page=gattiny">%s</a>',
			esc_html__( 'Settings', 'default' )
		);

		return $actionLinks;
	}
}

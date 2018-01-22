<?php

class gattiny_AdminScripts {

	public function enqueueScripts() {
		wp_enqueue_style(
			'gattiny-admin-style',
			plugins_url( 'assets/css/gattiny-admin.css', GATTINY_FILE )
		);
	}
}
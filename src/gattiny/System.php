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
		$this->mainFile = dirname(dirname(dirname(__FILE__))) . '/gattiny.php';
	}

	public function maybeDeactivate() {
		$plugin = plugin_basename($this->mainFile);
		if (!empty($_GET['activate']) && is_plugin_active($plugin) && current_user_can('activate_plugins')) {
			return;
		}
		if ('0' === get_option('gattiny_supported')) {
			unset($_GET['activate']);
			add_action('admin_notices', array($this, 'unsupportedNotice'));
		}
	}

	public function unsupportedNotice() {
		deactivate_plugins(plugin_basename($this->mainFile));
		?>
        <div class="notice notice-error gattiny_Notice gattiny_Notice--unsupported">
            <p><?php _e('Gattiny is not supported by your server!', 'gattiny'); ?></p>
        </div>
		<?php
	}
}

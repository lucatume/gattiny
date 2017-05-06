<?php
/*
Plugin Name: Gattiny
Plugin URI: https://wordpress.org/plugins/gattiny/
Description: Resize animated GIF images on upload.
Version: 0.1.0
Author: Luca Tumedei
Author URI: http://theaveragedev.com
Text Domain: gattiny
Domain Path: /languages
*/
add_action('admin_init', 'gattiny_maybeDeactivate');
function gattiny_maybeDeactivate() {
	$plugin = plugin_basename(__FILE__);
	if (!empty($_GET['activate']) && is_plugin_active($plugin) && current_user_can('activate_plugins')) {
		return;
	}
	if ('0' === get_option('gattiny_supported')) {
		unset($_GET['activate']);
		add_action('admin_notices', 'gattiny_unsupportedNotice');
		deactivate_plugins(plugin_basename(__FILE__));
	}
}

function gattiny_unsupportedNotice() {
	deactivate_plugins(plugin_basename(__FILE__));
	?>
    <div class="notice notice-error gattiny_Notice gattiny_Notice--unsupported">
        <p><?php _e('Gattiny is not supported by your server!', 'gattiny'); ?></p>
    </div>
	<?php
}

add_filter('wp_image_editors', 'gattiny_filterImageEditors');
function gattiny_filterImageEditors(array $imageEditors) {
	require_once dirname(__FILE__) . '/src/GifEditor.php';

	array_unshift($imageEditors, 'gattiny_GifEditor');

	return $imageEditors;
}


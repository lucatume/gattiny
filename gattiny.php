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

add_filter( 'wp_image_editors', 'gattiny_filterImageEditors' );
function gattiny_filterImageEditors( array $imageEditors ) {
	require_once dirname( __FILE__ ) . '/src/GifEditor.php';

	array_unshift( $imageEditors, 'gattiny_GifEditor' );

	return $imageEditors;
}

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

include_once dirname( __FILE__ ) . '/vendor/autoload_52.php';

$di = new tad_DI52_Container();

$di->singleton( 'gattiny.system', 'gattiny_System' );
$di->singleton( 'gattiny.image-editors', 'gattiny_ImageEditors' );

add_action( 'admin_init', $di->callback( 'gattiny.system', 'maybeDeactivate' ) );
add_filter( 'wp_image_editors', $di->callback( 'gattiny.image-editors', 'filterImageEditors' ) );

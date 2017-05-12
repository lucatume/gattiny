<?php

/**
 * Class gattiny_ImageEditors
 *
 * Manages the image editors provided by the plugin.
 */
class gattiny_ImageEditors {
	public function filterImageEditors(array $imageEditors) {
		if ('0' === get_option('gattiny_supported') || !extension_loaded('imagick')) {
			return $imageEditors;
		}

		array_unshift($imageEditors, 'gattiny_GifEditor');

		return $imageEditors;
	}
}
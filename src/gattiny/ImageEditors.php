<?php

/**
 * Class gattiny_ImageEditors
 *
 * Manages the image editors provided by the plugin.
 */
class gattiny_ImageEditors {
	public function filterImageEditors(array $imageEditors) {
		array_unshift($imageEditors, 'gattiny_GifEditor');

		return $imageEditors;
	}
}
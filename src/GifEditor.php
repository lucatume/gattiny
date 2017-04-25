<?php

class gattiny_GifEditor extends WP_Image_Editor_Imagick {

	public static function test($args = []) {
		return !empty($args['mime_type']) && $args['mime_type'] === 'image/gif';
	}

	public function multi_resize($sizes) {
		$metadata     = [];
		$original     = $this->image;
		$originalSize = $this->size;

		$testImage = $this->image->coalesceImages();

		if ($testImage->count() === 1)
		{
			return parent::multi_resize($sizes);
		}

		foreach ($sizes as $size => $data)
		{
			$resized = $this->resize($data['width'], $data['height'], $data['crop']);

			$duplicate = (($originalSize['width'] == $data['width']) && ($originalSize['height'] == $data['height']));

			if (!is_wp_error($resized) && !$duplicate)
			{
				$resized = $this->_save($this->image);

				$this->image->clear();
				$this->image->destroy();
				$this->image = NULL;

				if (!is_wp_error($resized) && $resized)
				{
					unset($resized['path']);
					$metadata[$size] = $resized;
				}
			}

			$this->size  = $originalSize;
			$this->image = $original;
		}


		return $metadata;
	}

	public function resize($max_w, $max_h, $crop = FALSE) {
		$testImage = $this->image->coalesceImages();

		if ($testImage->count() === 1)
		{
			return parent::resize($max_w, $max_h, $crop);
		}

		try
		{
			$this->image = $testImage;

			do
			{
				if ($crop)
				{
					$this->image->cropImage($max_w, $max_h, 0, 0);
					$this->image->setImagePage($max_w, $max_h, 0, 0);
				}
				else
				{
					$this->image->resizeImage($max_w, 0, Imagick::FILTER_BOX, 1, FALSE);
				}
			} while ($this->image->nextImage());

			$this->update_size($max_w, $this->image->getImageHeight());

			return TRUE;
		}
		catch (Exception $e)
		{
			return new WP_Error('gattiny-resize-error', __('Gattiny generated an error: ', 'gattiny') . $e->getMessage());
		}

	}

	public
	function _save(
		$image, $filename = NULL, $mime_type = NULL
	) {
		if ($this->image->count() === 1)
		{
			return parent::_save($image, $filename, $mime_type);
		}

		try
		{
			$this->image = $this->image->deconstructImages();
			$filename    = $this->generate_filename(NULL, NULL, 'gif');
			$this->image->writeImages($filename, TRUE);

			/** This filter is documented in wp-includes/class-wp-image-editor-gd.php */
			return [
				'path'      => $filename,
				'file'      => wp_basename(apply_filters('image_make_intermediate_size', $filename)),
				'width'     => $this->size['width'],
				'height'    => $this->size['height'],
				'mime-type' => $mime_type,
			];
		}
		catch (Exception $e)
		{
			return new WP_Error('gattiny-save-error', __('Gattiny generated an error: ', 'gattiny') . $e->getMessage());
		}

	}
}

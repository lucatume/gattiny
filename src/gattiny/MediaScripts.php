<?php

class gattiny_MediaScripts {

	public function printScripts() {
		echo '<style>
			.edit-attachment-frame img.details-image[src$=".gif"] + .attachment-actions .button.edit-attachment {
				display:none;
			}
			.edit-attachment-frame img.details-image[src$=".gif"] + .attachment-actions:before {
				content: "' . $this->editorMessage() . '";
			}
		</style>';
	}

	protected function editorMessage() {
		$editor = 'GIMP (https://www.gimp.org/)';

		return sprintf( esc_attr__( 'To edit GIF images use an external editor like %s before uploading them.', 'gattiny' ), $editor );
	}
}
<?php

class gattiny_Utils_Templates {

	public function compile( $template, $data = array() ) {
		ob_start();
		$this->render( $template, $data );

		return ob_get_clean();
	}

	public function render( $template, $data = array() ) {
		extract( $data, EXTR_OVERWRITE );
		include dirname( __FILE__ ) . "/../../../templates/{$template}.php";
	}
}
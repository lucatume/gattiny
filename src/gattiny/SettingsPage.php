<?php

class gattiny_SettingsPage {

	/**
	 * @var string
	 */
	public $page;

	/**
	 * @var gattiny_Utils_Templates
	 */
	protected $templates;

	/**
	 * @var gattiny_ImageSizes
	 */
	protected $imageSizes;

	public function __construct( gattiny_Utils_Templates $templates, gattiny_ImageSizes $imageSizes ) {
		$this->templates  = $templates;
		$this->imageSizes = $imageSizes;
	}

	public function addAdminMenu() {
		$this->page = add_submenu_page(
			'options-general.php',
			__( 'Gattiny', 'gattiny' ),
			__( 'Gattiny', 'gattiny' ),
			'manage_options',
			'gattiny',
			array( $this, 'render' )
		);
	}

	public function render( $echo = true ) {
		$compiled = $this->templates->compile( 'options-page', array(
			'title'          => __( 'Gattiny Settings' ),
			'page'           => $this->page,
			'optionGroup'    => 'gattiny',
			'headerImage'    => plugins_url( 'assets/images/gattiny-header.png', GATTINY_FILE ),
			'headerImageAlt' => __( 'Gattiny settings header image', 'gattiny' ),
		) );

		if ( false !== $echo ) {
			echo $compiled;
		}

		return $compiled;
	}

	public function initSettings() {
		register_setting( 'gattiny', gattiny_ImageSizes::OPTION );

		add_settings_section(
			gattiny_ImageSizes::OPTION,
			__( 'Image sizes', 'gattiny' ),
			array( $this, 'renderImageSizesSection' ),
			$this->page
		);

		add_settings_field(
			'gattiny-imageSizes-checkboxes',
			__( 'Decide how each image size conversion should be handled', 'gattiny' ),
			array( $this, 'imageSizesCheckboxes' ),
			$this->page,
			gattiny_ImageSizes::OPTION
		);
	}

	public function renderImageSizesSection() {
		echo '<p>',
		esc_html__(
			'Converting animated images, while preserving their animations, can potentially take a long time to finish. If you are experiencing an unresponsive UI or timeout messages use the settings below to tweak Gattiny behaviour.',
			'gattiny'
		),
		'</p><p>',
		esc_html__(
			'Images will never be upscaled to larger format; Gattiny will not change the default WordPress behaviour.',
			'gattiny'
		),
		'</p>';
	}

	public function imageSizesCheckboxes() {
		$option = get_option( gattiny_ImageSizes::OPTION );

		$conversionOptions = array(
			gattiny_ImageSizes::CONVERT_ANIMATED => __( 'Convert preserving animations %s', 'gattiny' ),
			gattiny_ImageSizes::CONVERT_STILL    => __( 'Convert removing animations (default WordPress behaviour)', 'gattiny' ),
			gattiny_ImageSizes::DO_NOT_CONVERT   => __( 'Do not convert', 'gattiny' ),
		);

		$lowThreshold    = $this->imageSizes->getLowThreshold();
		$mediumThreshold = $this->imageSizes->getMediumThreshold();

		/** @var gattiny_ImageSize $imageSize */
		foreach ( $this->imageSizes->getSizes() as $name => $imageSize ) {
			$imageSizeOption = isset( $option[ $name ] ) ? $option[ $name ] : $this->imageSizes->getDefaultConversionFor( $imageSize->getName() );
			$fields          = array();

			$width          = $imageSize->getWidth();
			$height         = $imageSize->getHeight();
			$crop           = $imageSize->isCropping() ? __( 'yes', 'gattiny' ) : __( 'no', 'gattiny' );
			$conversionCost = $imageSize->getConversionCost();

			if ( $conversionCost <= $lowThreshold ) {
				$loadSlug   = 'low';
				$loadString = __( 'fast conversion', 'gattiny' );
			} elseif ( $conversionCost <= $mediumThreshold ) {
				$loadSlug   = 'medium';
				$loadString = __( 'up to 2s to convert', 'gattiny' );
			} else {
				$loadSlug   = 'high';
				$loadString = __( 'takes time to convert', 'gattiny' );
			}
			$loadElement = "(<span class='load-{$loadSlug}'> - {$loadString}</span>)";

			foreach ( $conversionOptions as $slug => $label ) {
				$fields[] = $this->templates->compile( 'radio', array(
					'name'    => gattiny_ImageSizes::OPTION . '[' . $name . ']',
					'checked' => $imageSizeOption === $slug,
					'value'   => $slug,
					'label'   => gattiny_ImageSizes::CONVERT_ANIMATED === $slug ? sprintf( $label, $loadElement ) : $label,
				) );
			}

			$this->templates->render( 'fieldset', array(
				'legend' => sprintf(
					__( 'Image size %1$s - (w %2$dpx, h %3$dpx, cropping: %4$s)', 'gattiny' ),
					"<code>{$name}</code>",
					$width,
					$height,
					$crop
				),
				'fields' => $fields,
				'class'  => array( 'interline' ),
			) );
		}
	}
}

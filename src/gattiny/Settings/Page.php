<?php

class gattiny_Settings_Page {

	public $imageSizesOption = 'gattiny-imageSizes';

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

	public function render() {
		$this->templates->render( 'options-page', array(
			'title'       => __( 'Gattiny Settings' ),
			'page'        => $this->page,
			'optionGroup' => 'gattiny',
		) );
	}

	public function initSettings() {
		register_setting( 'gattiny', $this->imageSizesOption );

		add_settings_section(
			$this->imageSizesOption,
			__( 'Image sizes', 'gattiny' ),
			array( $this, 'renderImageSizesSection' ),
			$this->page
		);

		add_settings_field(
			'gattiny-imageSizes-checkboxes',
			__( 'Decide how each image size conversion should be handled', 'gattiny' ),
			array( $this, 'imageSizesCheckboxes' ),
			$this->page,
			$this->imageSizesOption
		);
	}

	public function renderImageSizesSection() {
		echo '<p>',
		esc_html__(
			'Converting animated images,  while preserving their animations, could potentially take a long time to finish. If you are experiencing an unresponsive UI or timeout messages use the settings below to tweak Gattiny behaviour.',
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
		$option = get_option( $this->imageSizesOption );

		$conversionOptions = array(
			'convert-animated' => __( 'Convert preserving animations %s', 'gattiny' ),
			'convert-still'    => __( 'Convert removing animations (default WordPress behaviour)', 'gattiny' ),
			'do-not-convert'   => __( 'Do not convert', 'gattiny' ),
		);

		$lowThreshold    = $this->imageSizes->getLowThreshold();
		$mediumThreshold = $this->imageSizes->getMediumThreshold();

		/** @var gattiny_ImageSize $imageSize */
		foreach ( $this->imageSizes->getSizes() as $name => $imageSize ) {
			$imageSizeOption = $option[ $name ];
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
					'name'    => "{$this->imageSizesOption}[" . $name . ']',
					'checked' => $imageSizeOption === $slug,
					'value'   => $slug,
					'label'   => 'convert-animated' === $slug ? sprintf( $label, $loadElement ) : $label,
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

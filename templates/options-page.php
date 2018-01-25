<form action='options.php' method='post' class="gattiny">

	<h1><?php echo $title ?></h1>

	<div>
		<img class="full-width" src="<?php echo $headerImage ?>" alt="<?php echo $headerImageAlt ?>">
	</div>

	<?php
	settings_fields( $optionGroup );
	do_settings_sections( $page );
	submit_button();
	?>

</form>

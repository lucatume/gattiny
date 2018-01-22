<form action='options.php' method='post' class="gattiny">

	<h2><?php echo $title ?></h2>

	<?php
	settings_fields( $optionGroup );
	do_settings_sections( $page );
	submit_button();
	?>

</form>

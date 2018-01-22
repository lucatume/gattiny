<fieldset class="<?php echo implode( ' ', $class ? $class : array() ) ?>">
	<legend><?php echo $legend ?></legend>
	<?php foreach ( $fields as $field ) : ?>
		<?php echo $field ?>
	<?php endforeach; ?>
</fieldset>

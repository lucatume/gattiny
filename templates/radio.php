<label for="<?php echo $name ?>" class="<?php echo implode( ' ', $class ? $class : array()) ?>">
	<input type="radio"
		   class="<?php echo implode( ' ', $inputClass ? $inputClass : array() ) ?>"
		   name="<?php echo $name ?>"
		<?php checked( $checked ); ?>
		   value="<?php echo $value ?>"
	>
	<?php echo $label ?>
</label>
<?php
// Just an empty form that will have a CSRF field
echo form_open('', 
		array(
			'id' => $id,
			'style' => 'display: none',
			));
		?>
</form>

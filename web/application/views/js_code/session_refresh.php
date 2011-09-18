<?php
// ms
$every *= 1000;
?>
var sr = setTimeout("session_refresh(<?php echo $every ?>)", <?php echo
		$every?>);

<?php
echo $header;
echo $navbar;
?>
<div class="container-fluid">
 <div class="row-fluid">
  <div class="span2" id="sidebar">
   <?php echo $sidebar; ?>
  </div>

  <div class="span10">
   <?php echo $content; ?>
  </div>
 </div>
</div>
<?php
echo $footer;
?>

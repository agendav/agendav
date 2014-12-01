<?php
echo $header;
echo $navbar;
?>
<div class="container-fluid">
 <div class="row">
  <div class="col-md-2" id="sidebar">
   <?php echo $sidebar; ?>
  </div>

  <div class="col-md-10">
   <?php echo $content; ?>
  </div>
 </div>
</div>
<?php
echo $footer;
?>

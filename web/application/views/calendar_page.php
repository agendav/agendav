<div id="page_contents">
	<div id="calendar_view">
	</div>
</div>

<div id="left_frame">

<?php
if (isset($logo)):
	$img = array(
        'src' => 'img/' . $logo,
        'alt' => $title,
        'title' => $title,
			);
	?>
 <div id="logo" class="block">
 <?php echo img($img); ?>
 </div>
 <?php
 endif;
?>

 <div id="shortcuts" class="block">
   <div id="shortcut_add_event">Crear evento</div>
 </div>

 <div id="calendar_list" class="ui-widget block">
  <div class="ui-widget-header ui-corner-all">Calendarios</div>
  <div class="ui-widget-content">
   <ul>
   </ul>
  <div class="links">
<?php
// Links
$img_add = array(
        'src' => 'img/calendar_add.png',
        'alt' => 'Añadir nuevo calendario',
        'title' => 'Añadir nuevo calendario',
		'id' => 'calendar_add',
        );

$img_refresh = array(
        'src' => 'img/arrow_refresh.png',
        'alt' => 'Refrescar',
        'title' => 'Refrescar',
		'id' => 'calendar_list_refresh',
        );

$items = array(
		img($img_add),
		img($img_refresh),
		);

foreach ($items as $item) {
	echo '<span class="item">' . $item . '</span>';
}

?> 
  </div>
  </div>
 </div>
</div>

<div id="popup" class="freeow freeow-top-right">
</div>

<div id="comm"></div>


var AgenDAVConf = <?php echo json_encode($options) ?>;

function set_default_colorpicker_options() {
    $.fn.colorPicker.defaultColors = AgenDAVConf.calendar_colors;
}

<?php
// vim: set ft=javascript

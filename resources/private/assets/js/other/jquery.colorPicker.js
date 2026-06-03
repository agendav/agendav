/**
 * Really Simple Color Picker in jQuery
 *
 * Copyright (c) 2008 Lakshan Perera (www.laktek.com)
 * Licensed under the MIT (MIT-LICENSE.txt)  licenses.
 *
 */

(function($){
  $.fn.colorPicker = function(){
    if(this.length > 0) buildSelector();
    return this.each(function(i) {
      buildPicker(this)});
  };

  var selectorOwner;
  var selectorShowing = false;

  buildPicker = function(element){
    //build color picker
    control = $("<div class='color_picker'>&nbsp;</div>")
    control.css('background-color', '#' + $(element).val());

    //bind click event to color picker
    control.bind("click", toggleSelector);

    //add the color picker section
    $(element).after(control);

    //add even listener to input box
    $(element).bind("change", function() {
      selectedValue = $(element).val();
      $(element).next(".color_picker").css("background-color", '#' + selectedValue);
    });

    //hide the input box
    $(element).hide();

  };

  buildSelector = function(){
    selector = $("<div id='color_selector'></div>");

    //add color pallete
    $.each($.fn.colorPicker.defaultColors, function(i, color){
      swatch = $("<div class='color_swatch'>&nbsp;</div>")
      swatch.css("background-color", '#' + color);
      swatch.bind("click", function(e){ changeColor(color) });
      swatch.bind("mouseover", function(e){
        $(this).css("border-color", "#598FEF");
        $("input#color_value").val(color);
      });
      swatch.bind("mouseout", function(e){
        $(this).css("border-color", "#000");
        $("input#color_value").val($(selectorOwner).prev("input").val());
      });

      swatch.appendTo(selector);
    });

    //add color value field
    color_field = $("<label for='color_value'>Color</label><input type='text' size='8' id='color_value'/>");
    color_field.bind("keydown", function(event){
      if(event.keyCode == 13) {changeColor($(this).val());}
      if(event.keyCode == 27) {toggleSelector()}
    });

    //add reset button
    reset_button = $("<button type='button' id='reset_color'>Reset color</button>");
    reset_button.bind("mouseover", function(e){
      var defaultColor = $.fn.colorPicker.calendarColor || '#FFFFFF';
      $("input#color_value").val(defaultColor);
    });
    reset_button.bind("mouseout", function(e){
      var currentColor = $(selectorOwner).prev("input").val();
      $("input#color_value").val(currentColor);
    });
    reset_button.bind("click", function(e){ resetColor(); });

    $("<div id='color_custom'></div>").append(color_field).append(reset_button).appendTo(selector);

    $("body").append(selector);
    selector.hide();
  };

  checkMouse = function(event){
    //check the click was on selector itself or on selectorOwner
    var selector = "div#color_selector";
    var selectorParent = $(event.target).parents(selector).length;
    if(event.target == $(selector)[0] || event.target == selectorOwner || selectorParent > 0) return

    hideSelector();
  }

  hideSelector = function(){
    var selector = $("div#color_selector");

    $(document).unbind("mousedown", checkMouse);
    selector.hide();
    selectorShowing = false
  }

  showSelector = function(){
    var selector = $("div#color_selector");

    selector.css({
      top: $(selectorOwner).offset().top + ($(selectorOwner).outerHeight()),
      left: $(selectorOwner).offset().left
    });
    colorValue = $(selectorOwner).prev("input").val();
    $("input#color_value").val(colorValue);
    selector.show();

    //bind close event handler
    $(document).bind("mousedown", checkMouse);
    selectorShowing = true
  }

  toggleSelector = function(event){
    selectorOwner = this;
    selectorShowing ? hideSelector() : showSelector();
  }

  changeColor = function(value){
    selectedValue = value;
    $(selectorOwner).css("background-color", '#' + selectedValue);
    $(selectorOwner).prev("input").val(selectedValue).change();

    //close the selector
    hideSelector();
  };

  resetColor = function(){
    var defaultColor = $.fn.colorPicker.calendarColor || 'FFFFFF';
    $(selectorOwner).prev("input").val(null).change();
    $(selectorOwner).css("background-color", '#' + defaultColor);

    // Update the color value input field
    $("input#color_value").val(defaultColor);

    //close the selector
    hideSelector();
  };

  //public methods
  $.fn.colorPicker.addColors = function(colorArray){
    $.fn.colorPicker.defaultColors = $.fn.colorPicker.defaultColors.concat(colorArray);
  };

  $.fn.colorPicker.defaultColors =
    [ '#000000', '#993300', '#333300', '#000080', '#333399', '#333333', '#800000', '#FF6600', '#808000', '#008000', '#008080', '#0000FF', '#666699', '#808080', '#FF0000', '#FF9900', '#99CC00', '#339966', '#33CCCC', '#3366FF', '#800080', '#999999', '#FF00FF', '#FFCC00', '#FFFF00', '#00FF00', '#00FFFF', '#00CCFF', '#993366', '#C0C0C0', '#FF99CC', '#FFCC99', '#FFFF99', '#CCFFFF', '#99CCFF', '#FFFFFF'];

})(jQuery);

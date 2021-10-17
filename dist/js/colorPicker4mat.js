/**
 * Adaptation of Really Simple Color Picker in jQuery
 */

(function($) {
  var uniqueId = (function() {
    var count = 0;
    return function() {
      return count++;
    };
  }());

  var isArray = window.Array.isArray || function(obj) {
    return window.toString.call(obj) == '[object Array]';
  };

  var isString = window.String.isString || function(obj) {
    return window.toString.call(obj) == '[object String]';
  };

  /**
   * Create our colorPicker function
  **/
  $.fn.colorPickerMat = function(options) {
    return this.each(function() {
      var $elem = $(this);
      if (!$elem.data('colorPicker')) {
        $elem.data('colorPicker', new ColorPickerMat(this, options));
      }
    });
  };

  var ColorPickerMat = function(input, options) {

    var self = this;

    // Setup time. Clone new elements from our templates, set some IDs, make shortcuts, jazzercise.
    this.element = $(input);
    this.options = $.extend({}, this.defaults, options);
	var optInd = this.element.val();	
	this.initialColor = this.toHex(this.options.colors[optInd]);
	this.element.attr('data-color', this.initialColor);
	
    this.container = this.templates.container.clone();
    this.control = this.templates.control.clone();
    this.palette = this.templates.palette.clone();
    this.id = 'colorPicker-palette-' + uniqueId();

    this.buildPalette(this.options.colors, this.options.optTxts);
    this.palette.attr('id', this.id);
    this.element.on("change.colorPicker", $.proxy(this.inputChange, this));
    this.control.on("click.colorPicker", $.proxy(this.controlClick, this));
    $(window).on('colorPicker:addSwatch.' + this.id, $.proxy(this.addSwatch, this));
    this.container.appendTo(this.palette);
	
    this.changeColor(this.initialColor);
    $(document.body).append(this.palette);

    this.element.before(this.control);
  };

  ColorPickerMat.prototype.createSwatch = function(index, color, optTxt) {
    var swatchContainer = this.templates.swatch.clone();
    var swatchLink = swatchContainer.find('a');
    var swatch = swatchContainer.find('.colorPicker-swatch');
	var optCode = swatchContainer.find('.colorPicker-swatch-text');

    color = this.toHex(color);
	optCode.text(optTxt);

    swatchContainer.attr('data-color', color);
    swatch.css('background', color);
    swatchLink.on('click.colorPicker', $.proxy(this.swatchLinkClick, this));

    return swatchContainer;
  };

  ColorPickerMat.prototype.swatchLinkClick = function(event) {
    event.preventDefault();
    $(event.currentTarget).trigger('colorPicker:swatchClick');
    return false;
  };

  ColorPickerMat.prototype.addSwatch = function(event, value) {
    var newSwatch = this.createSwatch(value);
    this.palette.find('.colorPicker-addSwatchContainer').before(newSwatch);

    this.element.trigger('colorPicker:addSwatch', value);
    $(window).trigger('colorPicker:addSwatch', value);
  };

  ColorPickerMat.prototype.controlClick = function(event) {
    if (this.element.not(':disabled')) {
      this.togglePalette($('#' + this.id), $(event.currentTarget));
    }
  };

  ColorPickerMat.prototype.inputChange = function(event) {
    var value = this.toHex($(event.currentTarget).val());
    this.control.css("background-color", value);
    this.element.trigger('colorPicker:change', value);
  };

  ColorPickerMat.prototype.swatchClick = function(event) {
    var $swatchContainer = $(event.currentTarget);
    this.changeColor($swatchContainer.attr('data-color'));
  };

  ColorPickerMat.prototype.swatchMouseover = function(event) {
    this.previewColor($(event.currentTarget).attr('data-color'));
  };

  ColorPickerMat.prototype.swatchMouseout = function(event) {
    this.previewColor(this.element.val());
  };

  /**
   * Return a Hex color, convert an RGB value and return Hex, or return false.
   *
   * Inspired by http://code.google.com/p/jquery-color-utils
  **/
  ColorPickerMat.prototype.toHex = function(color) {
	// If we have a standard or shorthand Hex color, return that value.
    if (color.match(/[0-9A-F]{6}|[0-9A-F]{3}$/i)) {
      return (color.charAt(0) === "#") ? color: ("#" + color);

    // Alternatively, check for RGB color, then convert and return it as Hex.
    }
    else if (color.match(/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/)) {
      var c = ([parseInt(RegExp.$1, 10), parseInt(RegExp.$2, 10), parseInt(RegExp.$3, 10)]),
        pad = function(str) {
          if (str.length < 2) {
            for (var i = 0, len = 2 - str.length; i < len; i++) {
              str = '0' + str;
            }
          }

          return str;
        };

      if (c.length === 3) {
        var r = pad(c[0].toString(16)),
          g = pad(c[1].toString(16)),
          b = pad(c[2].toString(16));

        return '#' + r + g + b;
      }

    // Otherwise we wont do anything.
    }
    else {
      return false;
    }
  };

  /**
   * Check whether user clicked on the selector or owner.
  **/
  ColorPickerMat.prototype.checkMouse = function(event) {
    var selectorParent = $(event.target).parents("#" + this.palette.attr('id')).length;

    if (event.target === $(this.palette)[0] || event.target === this.element[0] || selectorParent > 0) {
      return;
    }

    this.hidePalette();
  };

  /**
   * Hide the color palette modal.
  **/
  ColorPickerMat.prototype.hidePalette = function() {
    //TODO better solution than checkMouse?
    $(document).off("mousedown.colorPicker" + this.id, $.proxy(this.checkMouse, this));

    this.palette.hide();
  };

  /**
   * Show the color palette modal.
  **/
  ColorPickerMat.prototype.showPalette = function() {
    var hexColor = this.element.val();

    var offset = this.control.offset();

    this.palette.css({
      top: Math.min(offset.top + this.control.outerHeight(), $(document).height() - this.palette.outerHeight()),
      left: Math.min(offset.left, $(document).width() - this.palette.outerWidth())
    });

    this.palette.show();

    $(document).on("mousedown.colorPicker" + this.id, $.proxy(this.checkMouse, this));
  };

  /**
   * Toggle visibility of the colorPicker palette.
  **/
  ColorPickerMat.prototype.togglePalette = function(palette, origin) {
    if (this.palette.is(':visible'))
      this.hidePalette();
    else
      this.showPalette(palette);
  };

  /**
   * Update the input with a newly selected color.
  **/
  ColorPickerMat.prototype.changeColor = function(color) {
    this.control.css("background-color", color);
    this.element.attr('data-color', color);
	this.element.val(this.getColorIndex(color)).change();

    this.palette.find('.colorPicker-swatch-container.active').removeClass('active');
    this.palette.find('.colorPicker-swatch-container[data-color="' + color + '"]').addClass('active');

    this.hidePalette();
  };
  

  /**
   * Get Index associated to color
  **/
  ColorPickerMat.prototype.getColorIndex = function(color) {
	for (var i = 0; i < this.options.colors.length; i++) {
		if (this.options.colors[i] == color) return i;
	}
	return -1;
  };

  /**
   * Preview the input with a newly selected color.
  **/
  ColorPickerMat.prototype.previewColor = function(value, setHexFieldValue) {
    this.control.css("background-color", value);
    this.element.trigger('colorPicker:preview', value);
  };

  /**
   * Build a color palette.
  **/
  ColorPickerMat.prototype.buildPalette = function(colors, optTxts) {
    var self = this;
    var swatch;
    var callback = function(i, color, optTxt) {
      swatch = self.createSwatch(i, color, optTxt);
      swatch.appendTo(self.palette);
    };
	
	for (var i = 0; i < colors.length; i++) {
		callback(i, colors[i], optTxts[i]);
	}

    this.palette.on('colorPicker:swatchClick.colorPicker', '.colorPicker-swatch-container', $.proxy(this.swatchClick, this));
    this.palette.on('mouseover.colorPicker', '.colorPicker-swatch-container', $.proxy(this.swatchMouseover, this));
    this.palette.on('mouseout.colorPicker', '.colorPicker-swatch-container', $.proxy(this.swatchMouseout, this));
  };

  ColorPickerMat.prototype.templates = {
    container: $('<div class="colorPicker-addSwatchContainer" />'),
    control: $('<div class="colorPicker-picker add-on">&nbsp;</div>'),
    palette: $('<div class="colorPicker-palette dropdown-menu" />'),
    swatch : $('<li class="colorPicker-swatch-container"><a href="#"><div class="colorPicker-swatch">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">&nbsp;</div></a></li>'),
  };

  /**
   * Default colorPicker options.
   *
   * They can be applied on a per-bound element basis like so:
   *
   * $('#element1').colorPicker({pickerDefault: 'efefef', transparency: true});
   * $('#element2').colorPicker({pickerDefault: '333333', colors: ['333333', '111111']});
   *
  **/
  ColorPickerMat.prototype.defaults = {
    // colorPicker default selected color.
    pickerDefault: "#FFFFFF",

    // Default color set.
    colors: [
      '#FFFFFF',
      '#00FF00',
    ],
	
    // Default color set.
    optTxts: [
      'N/A',
      'Gratuit',
    ]
  };
  
})($);

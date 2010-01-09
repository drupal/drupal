// $Id: field_ui.js,v 1.2 2010/01/09 23:23:43 webchick Exp $

(function($) {

Drupal.behaviors.fieldManageFields = {
  attach: function (context) {
    attachUpdateSelects(context);
  }
};

function attachUpdateSelects(context) {
  var widgetTypes = Drupal.settings.fieldWidgetTypes;
  var fields = Drupal.settings.fields;

  // Store the default text of widget selects.
  $('#field-overview .widget-type-select', context).each(function () {
    this.initialValue = this.options[0].text;
  });

  // 'Field type' select updates its 'Widget' select.
  $('#field-overview .field-type-select', context).each(function () {
    this.targetSelect = $('.widget-type-select', $(this).parents('tr').eq(0));

    $(this).bind('change keyup', function () {
      var selectedFieldType = this.options[this.selectedIndex].value;
      var options = (selectedFieldType in widgetTypes ? widgetTypes[selectedFieldType] : []);
      this.targetSelect.fieldPopulateOptions(options);
    });

    // Trigger change on initial pageload to get the right widget options
    // when field type comes pre-selected (on failed validation).
    $(this).trigger('change', false);
  });

  // 'Existing field' select updates its 'Widget' select and 'Label' textfield.
  $('#field-overview .field-select', context).each(function () {
    this.targetSelect = $('.widget-type-select', $(this).parents('tr').eq(0));
    this.targetTextfield = $('.label-textfield', $(this).parents('tr').eq(0));

    $(this).bind('change keyup', function (e, updateText) {
      var updateText = (typeof updateText == 'undefined' ? true : updateText);
      var selectedField = this.options[this.selectedIndex].value;
      var selectedFieldType = (selectedField in fields ? fields[selectedField].type : null);
      var selectedFieldWidget = (selectedField in fields ? fields[selectedField].widget : null);
      var options = (selectedFieldType && (selectedFieldType in widgetTypes) ? widgetTypes[selectedFieldType] : []);
      this.targetSelect.fieldPopulateOptions(options, selectedFieldWidget);

      if (updateText) {
        $(this.targetTextfield).attr('value', (selectedField in fields ? fields[selectedField].label : ''));
      }
    });

    // Trigger change on initial pageload to get the right widget options
    // and label when field type comes pre-selected (on failed validation).
    $(this).trigger('change', false);
  });
}

jQuery.fn.fieldPopulateOptions = function (options, selected) {
  return this.each(function () {
    var disabled = false;
    if (options.length == 0) {
      options = [this.initialValue];
      disabled = true;
    }

    // If possible, keep the same widget selected when changing field type.
    // This is based on textual value, since the internal value might be
    // different (options_buttons vs. node_reference_buttons).
    var previousSelectedText = this.options[this.selectedIndex].text;

    var html = '';
    jQuery.each(options, function (value, text) {
      // Figure out which value should be selected. The 'selected' param
      // takes precedence.
      var is_selected = ((typeof selected != 'undefined' && value == selected) || (typeof selected == 'undefined' && text == previousSelectedText));
      html += '<option value="' + value + '"' + (is_selected ? ' selected="selected"' : '') + '>' + text + '</option>';
    });

    $(this).html(html).attr('disabled', disabled ? 'disabled' : '');
  });
};

})(jQuery);

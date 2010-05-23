// $Id: field_ui.js,v 1.3 2010/05/23 19:10:23 dries Exp $

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

/**
 * Moves a field in the display settings table from visible to hidden.
 *
 * This behavior is dependent on the tableDrag behavior, since it uses the
 * objects initialized in that behavior to update the row.
 */
Drupal.behaviors.fieldManageDisplayDrag = {
  attach: function (context, settings) {
    // tableDrag is required for this behavior.
    if (!$('table.field-display-overview', context).length || typeof Drupal.tableDrag == 'undefined') {
      return;
    }

    var defaultFormatters = Drupal.settings.fieldDefaultFormatters;
    var tableDrag = Drupal.tableDrag['field-display-overview'];

    // Add a handler for when a row is swapped, update empty regions.
    tableDrag.row.prototype.onSwap = function (swappedRow) {
      checkEmptyRegions(this.table, this);
    };

    // Add a handler to update the formatter selector when a row is dropped in
    // or out of the 'Hidden' section.
    tableDrag.onDrop = function () {
      var dragObject = this;
      var regionRow = $(dragObject.rowObject.element).prevAll('tr.region-message').get(0);
      var visibility = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');

      // Update the 'format' selector if the visibility changed.
      var $select = $('select.field-formatter-type', dragObject.rowObject.element);
      var oldVisibility = $select[0].className.replace(/([^ ]+[ ]+)*field-display-([^ ]+)([ ]+[^ ]+)*/, '$2');
      if (visibility != oldVisibility) {
        $select.removeClass('field-display-' + oldVisibility).addClass('field-display-' + visibility);

        // Update the selected formatter if coming from an actual drag.
        if (!$select.data('noUpdate')) {
          if (visibility == 'visible') {
            // Restore the formatter back to the previously selected one if
            // available, or to the default formatter.
            var value = $select.data('oldFormatter');
            if (typeof value == 'undefined') {
              // Extract field name from the name of the select.
              var fieldName = $select[0].className.match(/\bfield-name-(\S+)\b/)[1].replace('-', '_');
              // Pseudo-fields do not have an entry in the defaultFormatters
              // array, we just return to 'visible' for those.
              value = (fieldName in defaultFormatters) ? defaultFormatters[fieldName] : 'visible';
            }
            $select.data('oldFormatter', value);
          }
          else {
            var value = 'hidden';
          }
          $select.val(value);
        }
        $select.removeData('noUpdate');
      }
    };

    // Add the behavior to each formatter select list.
    $('select.field-formatter-type', context).once('field-formatter-type', function () {
      // Initialize 'previously selected formatter' as the incoming value.
      if ($(this).val() != 'hidden') {
        $(this).data('oldFormatter', $(this).val());
      }

      // Add change listener.
      $(this).change(function (event) {
        var $select = $(this);
        var value = $select.val();

        // Keep track of the last selected formatter.
        if (value != 'hidden') {
          $select.data('oldFormatter', value);
        }

        var visibility = (value == 'hidden') ? 'hidden' : 'visible';
        var oldVisibility = $select[0].className.replace(/([^ ]+[ ]+)*field-display-([^ ]+)([ ]+[^ ]+)*/, '$2');
        if (visibility != oldVisibility) {
          // Prevent the onDrop handler from overriding the selected option.
          $select.data('noUpdate', true);

          // Make our new row and select field.
          var $row = $(this).parents('tr:first');
          var $table = $(this).parents('table');
          var tableDrag = Drupal.tableDrag[$table.attr('id')];
          tableDrag.rowObject = new tableDrag.row($row);

          // Move the row at the bottom of the new section.
          if (visibility == 'hidden') {
            $('tr:last', tableDrag.table).after($row);
          }
          else {
            $('tr.region-title-hidden', tableDrag.table).before($row);
          }

          // Manually update weights and restripe.
          tableDrag.updateFields($row.get(0));
          tableDrag.rowObject.changed = true;
          if (tableDrag.oldRowElement) {
            $(tableDrag.oldRowElement).removeClass('drag-previous');
          }
          tableDrag.oldRowElement = $row.get(0);
          tableDrag.restripeTable();
          tableDrag.rowObject.markChanged();
          tableDrag.oldRowElement = $row;
          $row.addClass('drag-previous');

          // Modify empty regions with added or removed fields.
          checkEmptyRegions($table, tableDrag.rowObject);
        }

        // Remove focus from selectbox.
        $select.get(0).blur();
      });
    });

    var checkEmptyRegions = function ($table, rowObject) {
      $('tr.region-message', $table).each(function () {
        // If the dragged row is in this region, but above the message row, swap
        // it down one space.
        if ($(this).prev('tr').get(0) == rowObject.element) {
          // Prevent a recursion problem when using the keyboard to move rows up.
          if ((rowObject.method != 'keyboard' || rowObject.direction == 'down')) {
            rowObject.swap('after', this);
          }
        }
        // This region has become empty.
        if ($(this).next('tr').is(':not(.draggable)') || $(this).next('tr').length == 0) {
          $(this).removeClass('region-populated').addClass('region-empty');
        }
        // This region has become populated.
        else if ($(this).is('.region-empty')) {
          $(this).removeClass('region-empty').addClass('region-populated');
        }
      });
    };
  }
};

})(jQuery);

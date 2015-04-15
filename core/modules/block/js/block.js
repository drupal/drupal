(function ($, window) {

  "use strict";

  /**
   * Provide the summary information for the block settings vertical tabs.
   */
  Drupal.behaviors.blockSettingsSummary = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      function checkboxesSummary(context) {
        var vals = [];
        var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
        for (var i = 0, il = $checkboxes.length; i < il; i += 1) {
          vals.push($($checkboxes[i]).text());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('#edit-visibility-node-type, #edit-visibility-language, #edit-visibility-user-role').drupalSetSummary(checkboxesSummary);

      $('#edit-visibility-request-path').drupalSetSummary(function (context) {
        var $pages = $(context).find('textarea[name="visibility[request_path][pages]"]');
        if (!$pages.val()) {
          return Drupal.t('Not restricted');
        }
        else {
          return Drupal.t('Restricted to certain pages');
        }
      });
    }
  };

  /**
   * Move a block in the blocks table from one region to another via select list.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   */
  Drupal.behaviors.blockDrag = {
    attach: function (context, settings) {
      // tableDrag is required and we should be on the blocks admin page.
      if (typeof Drupal.tableDrag === 'undefined' || typeof Drupal.tableDrag.blocks === 'undefined') {
        return;
      }

      var table = $('#blocks');
      var tableDrag = Drupal.tableDrag.blocks; // Get the blocks tableDrag object.

      // Add a handler for when a row is swapped, update empty regions.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        checkEmptyRegions(table, this);
      };

      // Add a handler so when a row is dropped, update fields dropped into new regions.
      tableDrag.onDrop = function () {
        var dragObject = this;
        var $rowElement = $(dragObject.rowObject.element);
        // Use "region-message" row instead of "region" row because
        // "region-{region_name}-message" is less prone to regexp match errors.
        var regionRow = $rowElement.prevAll('tr.region-message').get(0);
        var regionName = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
        var regionField = $rowElement.find('select.block-region-select');
        // Check whether the newly picked region is available for this block.
        if (regionField.find('option[value=' + regionName + ']').length === 0) {
          // If not, alert the user and keep the block in its old region setting.
          window.alert(Drupal.t('The block cannot be placed in this region.'));
          // Simulate that there was a selected element change, so the row is put
          // back to from where the user tried to drag it.
          regionField.trigger('change');
        }
        else if ($rowElement.prev('tr').is('.region-message')) {
          var weightField = $rowElement.find('select.block-weight');
          var oldRegionName = weightField[0].className.replace(/([^ ]+[ ]+)*block-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');

          if (!regionField.is('.block-region-' + regionName)) {
            regionField.removeClass('block-region-' + oldRegionName).addClass('block-region-' + regionName);
            weightField.removeClass('block-weight-' + oldRegionName).addClass('block-weight-' + regionName);
            regionField.val(regionName);
          }
        }
      };

      // Add the behavior to each region select list.
      $(context).find('select.block-region-select').once('block-region-select').each(function () {
        $(this).on('change', function (event) {
          // Make our new row and select field.
          var row = $(this).closest('tr');
          var select = $(this);
          tableDrag.rowObject = new tableDrag.row(row);

          // Find the correct region and insert the row as the last in the region.
          table.find('.region-' + select[0].value + '-message').nextUntil('.region-message').eq(-1).before(row);

          // Modify empty regions with added or removed fields.
          checkEmptyRegions(table, row);
          // Remove focus from selectbox.
          select.trigger('blur');
        });
      });

      var checkEmptyRegions = function (table, rowObject) {
        table.find('tr.region-message').each(function () {
          var $this = $(this);
          // If the dragged row is in this region, but above the message row, swap it down one space.
          if ($this.prev('tr').get(0) === rowObject.element) {
            // Prevent a recursion problem when using the keyboard to move rows up.
            if ((rowObject.method !== 'keyboard' || rowObject.direction === 'down')) {
              rowObject.swap('after', this);
            }
          }
          // This region has become empty.
          if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
            $this.removeClass('region-populated').addClass('region-empty');
          }
          // This region has become populated.
          else if ($this.is('.region-empty')) {
            $this.removeClass('region-empty').addClass('region-populated');
          }
        });
      };
    }
  };

})(jQuery, window);

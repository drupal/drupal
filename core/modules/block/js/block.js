/**
 * @file
 * Block behaviors.
 */

(function ($, window) {

  'use strict';

  /**
   * Provide the summary information for the block settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.blockSettingsSummary = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      /**
       * Create a summary for checkboxes in the provided context.
       *
       * @param {HTMLDocument|HTMLElement} context
       *   A context where one would find checkboxes to summarize.
       *
       * @return {string}
       *   A string with the summary.
       */
      function checkboxesSummary(context) {
        var vals = [];
        var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
        var il = $checkboxes.length;
        for (var i = 0; i < il; i++) {
          vals.push($($checkboxes[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('[data-drupal-selector="edit-visibility-node-type"], [data-drupal-selector="edit-visibility-language"], [data-drupal-selector="edit-visibility-user-role"]').drupalSetSummary(checkboxesSummary);

      $('[data-drupal-selector="edit-visibility-request-path"]').drupalSetSummary(function (context) {
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
   * Move a block in the blocks table between regions via select list.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the tableDrag behaviour for blocks in block administration.
   */
  Drupal.behaviors.blockDrag = {
    attach: function (context, settings) {
      // tableDrag is required and we should be on the blocks admin page.
      if (typeof Drupal.tableDrag === 'undefined' || typeof Drupal.tableDrag.blocks === 'undefined') {
        return;
      }

      var table = $('#blocks');
      // Get the blocks tableDrag object.
      var tableDrag = Drupal.tableDrag.blocks;
      // Add a handler for when a row is swapped, update empty regions.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        checkEmptyRegions(table, this);
        updateLastPlaced(table, this);
      };

      // Add a handler so when a row is dropped, update fields dropped into
      // new regions.
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
          // If not, alert the user and keep the block in its old region
          // setting.
          window.alert(Drupal.t('The block cannot be placed in this region.'));
          // Simulate that there was a selected element change, so the row is
          // put back to from where the user tried to drag it.
          regionField.trigger('change');
        }

        // Update region and weight fields if the region has been changed.
        if (!regionField.is('.block-region-' + regionName)) {
          var weightField = $rowElement.find('select.block-weight');
          var oldRegionName = weightField[0].className.replace(/([^ ]+[ ]+)*block-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');
          regionField.removeClass('block-region-' + oldRegionName).addClass('block-region-' + regionName);
          weightField.removeClass('block-weight-' + oldRegionName).addClass('block-weight-' + regionName);
          regionField.val(regionName);
        }

        updateBlockWeights(table, regionName);
      };

      // Add the behavior to each region select list.
      $(context).find('select.block-region-select').once('block-region-select')
        .on('change', function (event) {
          // Make our new row and select field.
          var row = $(this).closest('tr');
          var select = $(this);
          // Find the correct region and insert the row as the last in the
          // region.
          tableDrag.rowObject = new tableDrag.row(row[0]);
          var region_message = table.find('.region-' + select[0].value + '-message');
          var region_items = region_message.nextUntil('.region-message, .region-title');
          if (region_items.length) {
            region_items.last().after(row);
          }
          // We found that region_message is the last row.
          else {
            region_message.after(row);
          }
          updateBlockWeights(table, select[0].value);
          // Modify empty regions with added or removed fields.
          checkEmptyRegions(table, tableDrag.rowObject);
          // Update last placed block indication.
          updateLastPlaced(table, row);
          // Show unsaved changes warning.
          if (!tableDrag.changed) {
            $(Drupal.theme('tableDragChangedWarning')).insertBefore(tableDrag.table).hide().fadeIn('slow');
            tableDrag.changed = true;
          }
          // Remove focus from selectbox.
          select.trigger('blur');
        });

      var updateLastPlaced = function ($table, rowObject) {
        // Remove the color-success class from new block if applicable.
        $table.find('.color-success').removeClass('color-success');

        var $rowObject = $(rowObject);
        if (!$rowObject.is('.drag-previous')) {
          $table.find('.drag-previous').removeClass('drag-previous');
          $rowObject.addClass('drag-previous');
        }
      };

      /**
       * Update block weights in the given region.
       *
       * @param {jQuery} $table
       *   Table with draggable items.
       * @param {string} region
       *   Machine name of region containing blocks to update.
       */
      var updateBlockWeights = function ($table, region) {
        // Calculate minimum weight.
        var weight = -Math.round($table.find('.draggable').length / 2);
        // Update the block weights.
        $table.find('.region-' + region + '-message').nextUntil('.region-title')
          .find('select.block-weight').val(function () {
            // Increment the weight before assigning it to prevent using the
            // absolute minimum available weight. This way we always have an
            // unused upper and lower bound, which makes manually setting the
            // weights easier for users who prefer to do it that way.
            return ++weight;
          });
      };

      /**
       * Checks empty regions and toggles classes based on this.
       *
       * @param {jQuery} table
       *   The jQuery object representing the table to inspect.
       * @param {jQuery} rowObject
       *   The jQuery object representing the table row.
       */
      var checkEmptyRegions = function (table, rowObject) {
        table.find('tr.region-message').each(function () {
          var $this = $(this);
          // If the dragged row is in this region, but above the message row,
          // swap it down one space.
          if ($this.prev('tr').get(0) === rowObject.element) {
            // Prevent a recursion problem when using the keyboard to move rows
            // up.
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

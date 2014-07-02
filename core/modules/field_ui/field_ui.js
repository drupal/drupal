/**
 * @file
 * Attaches the behaviors for the Field UI module.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.fieldUIDisplayOverview = {
    attach: function (context, settings) {
      $(context).find('table#field-display-overview').once('field-display-overview', function () {
        Drupal.fieldUIOverview.attach(this, settings.fieldUIRowsData, Drupal.fieldUIDisplayOverview);
      });
    }
  };

  Drupal.fieldUIOverview = {
    /**
     * Attaches the fieldUIOverview behavior.
     */
    attach: function (table, rowsData, rowHandlers) {
      var tableDrag = Drupal.tableDrag[table.id];

      // Add custom tabledrag callbacks.
      tableDrag.onDrop = this.onDrop;
      tableDrag.row.prototype.onSwap = this.onSwap;

      // Create row handlers.
      $(table).find('tr.draggable').each(function () {
        // Extract server-side data for the row.
        var row = this;
        if (row.id in rowsData) {
          var data = rowsData[row.id];
          data.tableDrag = tableDrag;

          // Create the row handler, make it accessible from the DOM row element.
          var rowHandler = new rowHandlers[data.rowHandler](row, data);
          $(row).data('fieldUIRowHandler', rowHandler);
        }
      });
    },

    /**
     * Event handler to be attached to form inputs triggering a region change.
     */
    onChange: function () {
      var $trigger = $(this);
      var $row = $trigger.closest('tr');
      var rowHandler = $row.data('fieldUIRowHandler');

      var refreshRows = {};
      refreshRows[rowHandler.name] = $trigger.get(0);

      // Handle region change.
      var region = rowHandler.getRegion();
      if (region !== rowHandler.region) {
        // Remove parenting.
        $row.find('select.field-parent').val('');
        // Let the row handler deal with the region change.
        $.extend(refreshRows, rowHandler.regionChange(region));
        // Update the row region.
        rowHandler.region = region;
      }

      // Ajax-update the rows.
      Drupal.fieldUIOverview.AJAXRefreshRows(refreshRows);
    },

    /**
     * Lets row handlers react when a row is dropped into a new region.
     */
    onDrop: function () {
      var dragObject = this;
      var row = dragObject.rowObject.element;
      var $row = $(row);
      var rowHandler = $row.data('fieldUIRowHandler');
      if (typeof rowHandler !== 'undefined') {
        var regionRow = $row.prevAll('tr.region-message').get(0);
        var region = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');

        if (region !== rowHandler.region) {
          // Let the row handler deal with the region change.
          var refreshRows = rowHandler.regionChange(region);
          // Update the row region.
          rowHandler.region = region;
          // Ajax-update the rows.
          Drupal.fieldUIOverview.AJAXRefreshRows(refreshRows);
        }
      }
    },

    /**
     * Refreshes placeholder rows in empty regions while a row is being dragged.
     *
     * Copied from block.js.
     *
     * @param table
     *   The table DOM element.
     * @param rowObject
     *   The tableDrag rowObject for the row being dragged.
     */
    onSwap: function (draggedRow) {
      var rowObject = this;
      $(rowObject.table).find('tr.region-message').each(function () {
        var $this = $(this);
        // If the dragged row is in this region, but above the message row, swap
        // it down one space.
        if ($this.prev('tr').get(0) === rowObject.group[rowObject.group.length - 1]) {
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
    },

    /**
     * Triggers Ajax refresh of selected rows.
     *
     * The 'format type' selects can trigger a series of changes in child rows.
     * The #ajax behavior is therefore not attached directly to the selects, but
     * triggered manually through a hidden #ajax 'Refresh' button.
     *
     * @param rows
     *   A hash object, whose keys are the names of the rows to refresh (they
     *   will receive the 'ajax-new-content' effect on the server side), and
     *   whose values are the DOM element in the row that should get an Ajax
     *   throbber.
     */
    AJAXRefreshRows: function (rows) {
      // Separate keys and values.
      var rowNames = [];
      var ajaxElements = [];
      var rowName;
      for (rowName in rows) {
        if (rows.hasOwnProperty(rowName)) {
          rowNames.push(rowName);
          ajaxElements.push(rows[rowName]);
        }
      }

      if (rowNames.length) {
        // Add a throbber next each of the ajaxElements.
        var $throbber = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
        $(ajaxElements)
          .addClass('progress-disabled')
          .after($throbber);

        // Fire the Ajax update.
        $('input[name=refresh_rows]').val(rowNames.join(' '));
        $('input#edit-refresh').trigger('mousedown');

        // Disabled elements do not appear in POST ajax data, so we mark the
        // elements disabled only after firing the request.
        $(ajaxElements).prop('disabled', true);
      }
    }
  };

  /**
   * Row handlers for the 'Manage display' screen.
   */
  Drupal.fieldUIDisplayOverview = {};

  /**
   * Constructor for a 'field' row handler.
   *
   * This handler is used for both fields and 'extra fields' rows.
   *
   * @param row
   *   The row DOM element.
   * @param data
   *   Additional data to be populated in the constructed object.
   */
  Drupal.fieldUIDisplayOverview.field = function (row, data) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;

    // Attach change listener to the 'plugin type' select.
    this.$pluginSelect = $(row).find('select.field-plugin-type');
    this.$pluginSelect.on('change', Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIDisplayOverview.field.prototype = {
    /**
     * Returns the region corresponding to the current form values of the row.
     */
    getRegion: function () {
      return (this.$pluginSelect.val() === 'hidden') ? 'hidden' : 'content';
    },

    /**
     * Reacts to a row being changed regions.
     *
     * This function is called when the row is moved to a different region, as a
     * result of either :
     * - a drag-and-drop action (the row's form elements then probably need to be
     *   updated accordingly)
     * - user input in one of the form elements watched by the
     *   Drupal.fieldUIOverview.onChange change listener.
     *
     * @param region
     *   The name of the new region for the row.
     * @return
     *   A hash object indicating which rows should be Ajax-updated as a result
     *   of the change, in the format expected by
     *   Drupal.displayOverview.AJAXRefreshRows().
     */
    regionChange: function (region) {

      // When triggered by a row drag, the 'format' select needs to be adjusted
      // to the new region.
      var currentValue = this.$pluginSelect.val();
      var value;
      // @TODO Check if this couldn't just be like
      // if (region !== 'hidden') {
      if (region === 'content') {
        if (currentValue === 'hidden') {
          // Restore the formatter back to the default formatter. Pseudo-fields do
          // not have default formatters, we just return to 'visible' for those.
          value = (typeof this.defaultPlugin !== 'undefined') ? this.defaultPlugin : this.$pluginSelect.find('option').val();
        }
      }
      else {
        value = 'hidden';
      }

      if (typeof value !== 'undefined') {
        this.$pluginSelect.val(value);
      }

      var refreshRows = {};
      refreshRows[this.name] = this.$pluginSelect.get(0);

      return refreshRows;
    }
  };

})(jQuery);

/**
 * @file
 * Attaches the behaviors for the Field UI module.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds behaviors to the field storage add form.
   */
  Drupal.behaviors.fieldUIFieldStorageAddForm = {
    attach(context) {
      const $form = $(context).find('[data-drupal-selector="field-ui-field-storage-add-form"]').once('field_ui_add');
      if ($form.length) {
        // Add a few 'js-form-required' and 'form-required' css classes here.
        // We can not use the Form API '#required' property because both label
        // elements for "add new" and "re-use existing" can never be filled and
        // submitted at the same time. The actual validation will happen
        // server-side.
        $form.find(
          '.js-form-item-label label,' +
          '.js-form-item-field-name label,' +
          '.js-form-item-existing-storage-label label')
          .addClass('js-form-required form-required');

        const $newFieldType = $form.find('select[name="new_storage_type"]');
        const $existingStorageName = $form.find('select[name="existing_storage_name"]');
        const $existingStorageLabel = $form.find('input[name="existing_storage_label"]');

        // When the user selects a new field type, clear the "existing field"
        // selection.
        $newFieldType.on('change', function () {
          if ($(this).val() !== '') {
            // Reset the "existing storage name" selection.
            $existingStorageName.val('').trigger('change');
          }
        });

        // When the user selects an existing storage name, clear the "new field
        // type" selection and populate the 'existing_storage_label' element.
        $existingStorageName.on('change', function () {
          const value = $(this).val();
          if (value !== '') {
            // Reset the "new field type" selection.
            $newFieldType.val('').trigger('change');

            // Pre-populate the "existing storage label" element.
            if (typeof drupalSettings.existingFieldLabels[value] !== 'undefined') {
              $existingStorageLabel.val(drupalSettings.existingFieldLabels[value]);
            }
          }
        });
      }
    },
  };

  /**
   * Attaches the fieldUIOverview behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the fieldUIOverview behavior.
   *
   * @see Drupal.fieldUIOverview.attach
   */
  Drupal.behaviors.fieldUIDisplayOverview = {
    attach(context, settings) {
      $(context).find('table#field-display-overview').once('field-display-overview').each(function () {
        Drupal.fieldUIOverview.attach(this, settings.fieldUIRowsData, Drupal.fieldUIDisplayOverview);
      });
    },
  };

  /**
   * Namespace for the field UI overview.
   *
   * @namespace
   */
  Drupal.fieldUIOverview = {

    /**
     * Attaches the fieldUIOverview behavior.
     *
     * @param {HTMLTableElement} table
     *   The table element for the overview.
     * @param {object} rowsData
     *   The data of the rows in the table.
     * @param {object} rowHandlers
     *   Handlers to be added to the rows.
     */
    attach(table, rowsData, rowHandlers) {
      const tableDrag = Drupal.tableDrag[table.id];

      // Add custom tabledrag callbacks.
      tableDrag.onDrop = this.onDrop;
      tableDrag.row.prototype.onSwap = this.onSwap;

      // Create row handlers.
      $(table).find('tr.draggable').each(function () {
        // Extract server-side data for the row.
        const row = this;
        if (row.id in rowsData) {
          const data = rowsData[row.id];
          data.tableDrag = tableDrag;

          // Create the row handler, make it accessible from the DOM row
          // element.
          const rowHandler = new rowHandlers[data.rowHandler](row, data);
          $(row).data('fieldUIRowHandler', rowHandler);
        }
      });
    },

    /**
     * Event handler to be attached to form inputs triggering a region change.
     */
    onChange() {
      const $trigger = $(this);
      const $row = $trigger.closest('tr');
      const rowHandler = $row.data('fieldUIRowHandler');

      const refreshRows = {};
      refreshRows[rowHandler.name] = $trigger.get(0);

      // Handle region change.
      const region = rowHandler.getRegion();
      if (region !== rowHandler.region) {
        // Remove parenting.
        $row.find('select.js-field-parent').val('');
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
    onDrop() {
      const dragObject = this;
      const row = dragObject.rowObject.element;
      const $row = $(row);
      const rowHandler = $row.data('fieldUIRowHandler');
      if (typeof rowHandler !== 'undefined') {
        const regionRow = $row.prevAll('tr.region-message').get(0);
        const region = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');

        if (region !== rowHandler.region) {
          // Let the row handler deal with the region change.
          const refreshRows = rowHandler.regionChange(region);
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
     * @param {HTMLElement} draggedRow
     *   The tableDrag rowObject for the row being dragged.
     */
    onSwap(draggedRow) {
      const rowObject = this;
      $(rowObject.table).find('tr.region-message').each(function () {
        const $this = $(this);
        // If the dragged row is in this region, but above the message row, swap
        // it down one space.
        if ($this.prev('tr').get(0) === rowObject.group[rowObject.group.length - 1]) {
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
    },

    /**
     * Triggers Ajax refresh of selected rows.
     *
     * The 'format type' selects can trigger a series of changes in child rows.
     * The #ajax behavior is therefore not attached directly to the selects, but
     * triggered manually through a hidden #ajax 'Refresh' button.
     *
     * @param {object} rows
     *   A hash object, whose keys are the names of the rows to refresh (they
     *   will receive the 'ajax-new-content' effect on the server side), and
     *   whose values are the DOM element in the row that should get an Ajax
     *   throbber.
     */
    AJAXRefreshRows(rows) {
      // Separate keys and values.
      const rowNames = [];
      const ajaxElements = [];
      Object.keys(rows || {}).forEach((rowName) => {
        rowNames.push(rowName);
        ajaxElements.push(rows[rowName]);
      });

      if (rowNames.length) {
        // Add a throbber next each of the ajaxElements.
        $(ajaxElements).after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');

        // Fire the Ajax update.
        $('input[name=refresh_rows]').val(rowNames.join(' '));
        $('input[data-drupal-selector="edit-refresh"]').trigger('mousedown');

        // Disabled elements do not appear in POST ajax data, so we mark the
        // elements disabled only after firing the request.
        $(ajaxElements).prop('disabled', true);
      }
    },
  };

  /**
   * Row handlers for the 'Manage display' screen.
   *
   * @namespace
   */
  Drupal.fieldUIDisplayOverview = {};

  /**
   * Constructor for a 'field' row handler.
   *
   * This handler is used for both fields and 'extra fields' rows.
   *
   * @constructor
   *
   * @param {HTMLTableRowElement} row
   *   The row DOM element.
   * @param {object} data
   *   Additional data to be populated in the constructed object.
   *
   * @return {Drupal.fieldUIDisplayOverview.field}
   *   The field row handler constructed.
   */
  Drupal.fieldUIDisplayOverview.field = function (row, data) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;
    this.defaultPlugin = data.defaultPlugin;

    // Attach change listener to the 'plugin type' select.
    this.$pluginSelect = $(row).find('.field-plugin-type');
    this.$pluginSelect.on('change', Drupal.fieldUIOverview.onChange);

    // Attach change listener to the 'region' select.
    this.$regionSelect = $(row).find('select.field-region');
    this.$regionSelect.on('change', Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIDisplayOverview.field.prototype = {

    /**
     * Returns the region corresponding to the current form values of the row.
     *
     * @return {string}
     *   Either 'hidden' or 'content'.
     */
    getRegion() {
      return this.$regionSelect.val();
    },

    /**
     * Reacts to a row being changed regions.
     *
     * This function is called when the row is moved to a different region, as
     * a
     * result of either :
     * - a drag-and-drop action (the row's form elements then probably need to
     * be updated accordingly)
     * - user input in one of the form elements watched by the
     *   {@link Drupal.fieldUIOverview.onChange} change listener.
     *
     * @param {string} region
     *   The name of the new region for the row.
     *
     * @return {object}
     *   A hash object indicating which rows should be Ajax-updated as a result
     *   of the change, in the format expected by
     *   {@link Drupal.fieldUIOverview.AJAXRefreshRows}.
     */
    regionChange(region) {
      // Replace dashes with underscores.
      region = region.replace(/-/g, '_');

      // Set the region of the select list.
      this.$regionSelect.val(region);

      // Restore the formatter back to the default formatter. Pseudo-fields
      // do not have default formatters, we just return to 'visible' for
      // those.
      const value = (typeof this.defaultPlugin !== 'undefined') ? this.defaultPlugin : this.$pluginSelect.find('option').val();

      if (typeof value !== 'undefined') {
        this.$pluginSelect.val(value);
      }

      const refreshRows = {};
      refreshRows[this.name] = this.$pluginSelect.get(0);

      return refreshRows;
    },
  };
}(jQuery, Drupal, drupalSettings));

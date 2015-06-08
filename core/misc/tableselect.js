/**
 * @file
 * Table select functionality.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Initialize tableSelects.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableSelect = {
    attach: function (context, settings) {
      // Select the inner-most table in case of nested tables.
      $(context).find('th.select-all').closest('table').once('table-select').each(Drupal.tableSelect);
    }
  };

  /**
   * Callback used in {@link Drupal.behaviors.tableSelect}.
   */
  Drupal.tableSelect = function () {
    // Do not add a "Select all" checkbox if there are no rows with checkboxes
    // in the table.
    if ($(this).find('td input[type="checkbox"]').length === 0) {
      return;
    }

    // Keep track of the table, which checkbox is checked and alias the
    // settings.
    var table = this;
    var checkboxes;
    var lastChecked;
    var $table = $(table);
    var strings = {'selectAll': Drupal.t('Select all rows in this table'), 'selectNone': Drupal.t('Deselect all rows in this table')};
    var updateSelectAll = function (state) {
      // Update table's select-all checkbox (and sticky header's if available).
      $table.prev('table.sticky-header').addBack().find('th.select-all input[type="checkbox"]').each(function () {
        $(this).attr('title', state ? strings.selectNone : strings.selectAll);

        /**
         * @this {HTMLElement}
         */
        this.checked = state;
      });
    };

    // Find all <th> with class select-all, and insert the check all checkbox.
    $table.find('th.select-all').prepend($('<input type="checkbox" class="form-checkbox" />').attr('title', strings.selectAll)).on('click', function (event) {
      if ($(event.target).is('input[type="checkbox"]')) {
        // Loop through all checkboxes and set their state to the select all
        // checkbox' state.
        checkboxes.each(function () {

          /**
           * @this {HTMLElement}
           */
          this.checked = event.target.checked;
          // Either add or remove the selected class based on the state of the
          // check all checkbox.

          /**
           * @this {HTMLElement}
           */
          $(this).closest('tr').toggleClass('selected', this.checked);
        });
        // Update the title and the state of the check all box.
        updateSelectAll(event.target.checked);
      }
    });

    // For each of the checkboxes within the table that are not disabled.
    checkboxes = $table.find('td input[type="checkbox"]:enabled').on('click', function (e) {
      // Either add or remove the selected class based on the state of the
      // check all checkbox.

      /**
       * @this {HTMLElement}
       */
      $(this).closest('tr').toggleClass('selected', this.checked);

      // If this is a shift click, we need to highlight everything in the
      // range. Also make sure that we are actually checking checkboxes
      // over a range and that a checkbox has been checked or unchecked before.
      if (e.shiftKey && lastChecked && lastChecked !== e.target) {
        // We use the checkbox's parent TR to do our range searching.
        Drupal.tableSelectRange($(e.target).closest('tr')[0], $(lastChecked).closest('tr')[0], e.target.checked);
      }

      // If all checkboxes are checked, make sure the select-all one is checked
      // too, otherwise keep unchecked.
      updateSelectAll((checkboxes.length === checkboxes.filter(':checked').length));

      // Keep track of the last checked checkbox.
      lastChecked = e.target;
    });

    // If all checkboxes are checked on page load, make sure the select-all one
    // is checked too, otherwise keep unchecked.
    updateSelectAll((checkboxes.length === checkboxes.filter(':checked').length));
  };

  /**
   * @param {HTMLElement} from
   * @param {HTMLElement} to
   * @param {bool} state
   */
  Drupal.tableSelectRange = function (from, to, state) {
    // We determine the looping mode based on the order of from and to.
    var mode = from.rowIndex > to.rowIndex ? 'previousSibling' : 'nextSibling';

    // Traverse through the sibling nodes.
    for (var i = from[mode]; i; i = i[mode]) {
      var $i;
      // Make sure that we're only dealing with elements.
      if (i.nodeType !== 1) {
        continue;
      }
      $i = $(i);
      // Either add or remove the selected class based on the state of the
      // target checkbox.
      $i.toggleClass('selected', state);
      $i.find('input[type="checkbox"]').prop('checked', state);

      if (to.nodeType) {
        // If we are at the end of the range, stop.
        if (i === to) {
          break;
        }
      }
      // A faster alternative to doing $(i).filter(to).length.
      else if ($.filter(to, [i]).r.length) {
        break;
      }
    }
  };

})(jQuery, Drupal);

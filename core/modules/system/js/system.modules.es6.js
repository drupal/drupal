/**
 * @file
 * Module page behaviors.
 */

(function ($, Drupal, debounce) {

  'use strict';

  /**
   * Filters the module list table by a text input search string.
   *
   * Additionally accounts for multiple tables being wrapped in "package" details
   * elements.
   *
   * Text search input: input.table-filter-text
   * Target table:      input.table-filter-text[data-table]
   * Source text:       .table-filter-text-source, .module-name, .module-description
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableFilterByText = {
    attach: function (context, settings) {
      var $input = $('input.table-filter-text').once('table-filter-text');
      var $table = $($input.attr('data-table'));
      var $rowsAndDetails;
      var $rows;
      var $details;
      var searching = false;

      function hidePackageDetails(index, element) {
        var $packDetails = $(element);
        var $visibleRows = $packDetails.find('tbody tr:visible');
        $packDetails.toggle($visibleRows.length > 0);
      }

      function filterModuleList(e) {
        var query = $(e.target).val();
        // Case insensitive expression to find query at the beginning of a word.
        var re = new RegExp('\\b' + query, 'i');

        function showModuleRow(index, row) {
          var $row = $(row);
          var $sources = $row.find('.table-filter-text-source, .module-name, .module-description');
          var textMatch = $sources.text().search(re) !== -1;
          $row.closest('tr').toggle(textMatch);
        }
        // Search over all rows and packages.
        $rowsAndDetails.show();

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          searching = true;
          $rows.each(showModuleRow);

          // Note that we first open all <details> to be able to use ':visible'.
          // Mark the <details> elements that were closed before filtering, so
          // they can be reclosed when filtering is removed.
          $details.not('[open]').attr('data-drupal-system-state', 'forced-open');

          // Hide the package <details> if they don't have any visible rows.
          // Note that we first show() all <details> to be able to use ':visible'.
          $details.attr('open', true).each(hidePackageDetails);

          Drupal.announce(
            Drupal.t(
              '!modules modules are available in the modified list.',
              {'!modules': $rowsAndDetails.find('tbody tr:visible').length}
            )
          );
        }
        else if (searching) {
          searching = false;
          $rowsAndDetails.show();
          // Return <details> elements that had been closed before filtering
          // to a closed state.
          $details.filter('[data-drupal-system-state="forced-open"]')
            .removeAttr('data-drupal-system-state')
            .attr('open', false);
        }
      }

      function preventEnterKey(event) {
        if (event.which === 13) {
          event.preventDefault();
          event.stopPropagation();
        }
      }

      if ($table.length) {
        $rowsAndDetails = $table.find('tr, details');
        $rows = $table.find('tbody tr');
        $details = $rowsAndDetails.filter('.package-listing');

        $input.on({
          keyup: debounce(filterModuleList, 200),
          keydown: preventEnterKey
        });
      }
    }
  };

}(jQuery, Drupal, Drupal.debounce));

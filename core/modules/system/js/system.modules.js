/**
 * @file
 * Module page behaviors.
 */

(function ($, Drupal, debounce) {
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
    attach(context, settings) {
      const [input] = once('table-filter-text', 'input.table-filter-text');
      if (!input) {
        return;
      }
      const $table = $(input.getAttribute('data-table'));
      let $rowsAndDetails;
      let $rows;
      let $details;
      let searching = false;

      function hidePackageDetails(index, element) {
        const $packDetails = $(element);
        const $visibleRows = $packDetails.find('tbody tr:visible');
        $packDetails.toggle($visibleRows.length > 0);
      }

      function filterModuleList(e) {
        const query = e.target.value;
        // Case insensitive expression to find query at the beginning of a word.
        const re = new RegExp(`\\b${query}`, 'i');

        function showModuleRow(index, row) {
          const sources = row.querySelectorAll(
            '.table-filter-text-source, .module-name, .module-description',
          );
          let sourcesConcat = '';
          // Concatenate the textContent of the elements in the row, with a
          // space in between.
          sources.forEach((item) => {
            sourcesConcat += ` ${item.textContent}`;
          });
          const textMatch = sourcesConcat.search(re) !== -1;
          $(row).closest('tr').toggle(textMatch);
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
          $details
            .not('[open]')
            .attr('data-drupal-system-state', 'forced-open');

          // Hide the package <details> if they don't have any visible rows.
          // Note that we first show() all <details> to be able to use ':visible'.
          $details.attr('open', true).each(hidePackageDetails);

          Drupal.announce(
            Drupal.formatPlural(
              $rowsAndDetails.filter('tbody tr:visible').length,
              '1 module is available in the modified list.',
              '@count modules are available in the modified list.',
            ),
          );
        } else if (searching) {
          searching = false;
          $rowsAndDetails.show();
          // Return <details> elements that had been closed before filtering
          // to a closed state.
          $details
            .filter('[data-drupal-system-state="forced-open"]')
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

        $(input).on({
          input: debounce(filterModuleList, 200),
          keydown: preventEnterKey,
        });
      }
    },
  };
})(jQuery, Drupal, Drupal.debounce);

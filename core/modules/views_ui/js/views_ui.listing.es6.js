/**
 * @file
 * Views listing behaviors.
 */

(function ($, Drupal) {
  /**
   * Filters the view listing tables by a text input search string.
   *
   * Text search input: input.views-filter-text
   * Target table:      input.views-filter-text[data-table]
   * Source text:       [data-drupal-selector="views-table-filter-text-source"]
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the filter functionality to the views admin text search field.
   */
  Drupal.behaviors.viewTableFilterByText = {
    attach(context, settings) {
      const [input] = once('views-filter-text', 'input.views-filter-text');
      if (!input) {
        return;
      }
      const $table = $(input.getAttribute('data-table'));
      let $rows;

      function filterViewList(e) {
        const query = e.target.value.toLowerCase();

        function showViewRow(index, row) {
          const sources = row.querySelectorAll(
            '[data-drupal-selector="views-table-filter-text-source"]',
          );
          let sourcesConcat = '';
          sources.forEach((item) => {
            sourcesConcat += item.textContent;
          });
          const textMatch = sourcesConcat.toLowerCase().indexOf(query) !== -1;
          $(row).closest('tr').toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $rows.each(showViewRow);
        } else {
          $rows.show();
        }
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $(input).on('keyup', filterViewList);
      }
    },
  };
})(jQuery, Drupal);

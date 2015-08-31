/**
 * @file
 * Views listing behaviors.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Filters the view listing tables by a text input search string.
   *
   * Text search input: input.views-filter-text
   * Target table:      input.views-filter-text[data-table]
   * Source text:       .views-table-filter-text-source
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the filter functionality to the views admin text search field.
   */
  Drupal.behaviors.viewTableFilterByText = {
    attach: function (context, settings) {
      var $input = $('input.views-filter-text').once('views-filter-text');
      var $table = $($input.attr('data-table'));
      var $rows;

      function filterViewList(e) {
        var query = $(e.target).val().toLowerCase();

        function showViewRow(index, row) {
          var $row = $(row);
          var $sources = $row.find('.views-table-filter-text-source');
          var textMatch = $sources.text().toLowerCase().indexOf(query) !== -1;
          $row.closest('tr').toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $rows.each(showViewRow);
        }
        else {
          $rows.show();
        }
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $input.on('keyup', filterViewList);
      }
    }
  };

}(jQuery, Drupal));

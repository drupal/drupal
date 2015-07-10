/**
 * @file
 * Block admin behaviors.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Filters the block list by a text input search string.
   *
   * Text search input: input.block-filter-text
   * Target element:    input.block-filter-text[data-element]
   * Source text:       .block-filter-text-source
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blockFilterByText = {
    attach: function (context, settings) {
      var $input = $('input.block-filter-text').once('block-filter-text');
      var $table = $($input.attr('data-element'));
      var $filter_rows;

      function filterBlockList(e) {
        var query = $(e.target).val().toLowerCase();

        /**
         * Shows or hides the block entry based on the query.
         *
         * @param {number} index The index of the block.
         * @param {HTMLElement} label The label of the block.
         */
        function toggleBlockEntry(index, label) {
          var $label = $(label);
          var $row = $label.parent().parent();
          var textMatch = $label.text().toLowerCase().indexOf(query) !== -1;
          $row.toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $filter_rows.each(toggleBlockEntry);
        }
        else {
          $filter_rows.each(function (index) {
            $(this).parent().parent().show();
          });
        }
      }

      if ($table.length) {
        $filter_rows = $table.find('div.block-filter-text-source');
        $input.on('keyup', filterBlockList);
      }
    }
  };

  /**
   * Highlights the block that was just placed into the block listing.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blockHighlightPlacement = {
    attach: function (context, settings) {
      if (settings.blockPlacement) {
        $(context).find('[data-drupal-selector="edit-blocks"]').once('block-highlight').each(function () {
          var $container = $(this);
          // Just scrolling the document.body will not work in Firefox. The html
          // element is needed as well.
          $('html, body').animate({
            scrollTop: $('.js-block-placed').offset().top - $container.offset().top + $container.scrollTop()
          }, 500);
        });
      }
    }
  };

}(jQuery, Drupal));

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
      var $element = $($input.attr('data-element'));
      var $blocks;
      var $details;

      /**
       * Hides the `<details>` element for a category if it has no visible blocks.
       *
       * @param {number} index
       * @param {HTMLElement} element
       */
      function hideCategoryDetails(index, element) {
        var $catDetails = $(element);
        $catDetails.toggle($catDetails.find('li:visible').length > 0);
      }

      /**
       * Filters the block list.
       *
       * @param {jQuery.Event} e
       */
      function filterBlockList(e) {
        var query = $(e.target).val().toLowerCase();

        /**
         * Shows or hides the block entry based on the query.
         *
         * @param {number} index
         * @param {HTMLElement} block
         */
        function showBlockEntry(index, block) {
          var $block = $(block);
          var $sources = $block.find('.block-filter-text-source');
          var textMatch = $sources.text().toLowerCase().indexOf(query) !== -1;
          $block.toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $blocks.each(showBlockEntry);

          // Note that we first open all <details> to be able to use ':visible'.
          // Mark the <details> elements that were closed before filtering, so
          // they can be reclosed when filtering is removed.
          $details.not('[open]').attr('data-drupal-block-state', 'forced-open');
          // Hide the category <details> if they don't have any visible rows.
          $details.attr('open', 'open').each(hideCategoryDetails);
        }
        else {
          $blocks.show();
          $details.show();
          // Return <details> elements that had been closed before filtering
          // to a closed state.
          $details.filter('[data-drupal-block-state="forced-open"]').removeAttr('open data-drupal-block-state');
        }
      }

      if ($element.length) {
        $details = $element.find('details');
        $blocks = $details.find('li');

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

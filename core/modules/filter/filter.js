/**
 * @file
 * Attaches behavior for the Filter module.
 */

(function ($) {

"use strict";

/**
 * Displays the guidelines of the selected text format automatically.
 */
Drupal.behaviors.filterGuidelines = {
  attach: function (context) {
    $(context).find('.filter-guidelines').once('filter-guidelines')
      .find(':header').hide()
      .closest('.filter-wrapper').find('select.filter-list')
      .on('change', function () {
        $(this).closest('.filter-wrapper')
          .find('.filter-guidelines-item').hide()
          .filter('.filter-guidelines-' + this.value).show();
      })
      .trigger('change');
  }
};

})(jQuery);

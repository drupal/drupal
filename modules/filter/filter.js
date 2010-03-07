// $Id: filter.js,v 1.1 2010/03/07 23:59:20 webchick Exp $
(function ($) {

/**
 * Automatically display the guidelines of the selected text format.
 */
Drupal.behaviors.filterGuidelines = {
  attach: function (context) {
    $('.filter-guidelines', context).once('filter-guidelines')
      .find('label').hide()
      .parents('.filter-wrapper').find('select.filter-list')
      .bind('change', function () {
        $(this).parents('.filter-wrapper')
          .find('.filter-guidelines-item').hide()
          .siblings('#filter-guidelines-' + this.value).show();
      })
      .change();
  }
};

})(jQuery);

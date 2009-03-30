// $Id$
(function($) {

Drupal.behaviors.multiselectSelector = {
  attach: function(context, settings) {
    // Automatically selects the right radio button in a multiselect control.
    $('.multiselect select:not(.multiselectSelector-processed)', context)
      .addClass('multiselectSelector-processed').change(function() {
        $('.multiselect input:radio[value="'+ this.id.substr(5) +'"]')
          .attr('checked', true);
    });
  }
};


/**
 * Automatically display the guidelines of the selected text format.
 */
Drupal.behaviors.filterGuidelines = {
  attach: function(context) {
    $('.filter-guidelines:not(.filter-guidelines-processed)', context)
      .addClass('filter-guidelines-processed')
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

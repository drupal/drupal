// $Id$

Drupal.behaviors.multiselectSelector = {
  attach: function(context) {
    // Automatically selects the right radio button in a multiselect control.
    $('.multiselect select:not(.multiselectSelector-processed)', context)
      .addClass('multiselectSelector-processed').change(function() {
        $('.multiselect input:radio[value="'+ this.id.substr(5) +'"]')
          .attr('checked', true);
    });
  }
};

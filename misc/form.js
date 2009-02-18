// $Id: form.js,v 1.3 2009/02/18 13:46:52 webchick Exp $
(function($) {

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

})(jQuery);

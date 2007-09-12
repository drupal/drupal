// $Id: form.js,v 1.1 2007/09/12 18:29:32 goba Exp $

Drupal.behaviors.multiselectSelector = function() {
  // Automatically selects the right radio button in a multiselect control.
  $('.multiselect select:not(.multiselectSelector-processed)')
    .addClass('multiselectSelector-processed').change(function() {
      $('.multiselect input:radio[value="'+ this.id.substr(5) +'"]')
        .attr('checked', true);
  });
};

/**
 * @file
 * Javascript related to the main view list.
 */
(function ($) {

"use strict";

Drupal.behaviors.viewsUIList = {
  attach: function (context) {
    $('#ctools-export-ui-list-items thead a').once('views-ajax-processed').each(function() {
      $(this).click(function() {
        var query = $.deparam.querystring(this.href);
        $('#ctools-export-ui-list-form select[name=order]').val(query.order);
        $('#ctools-export-ui-list-form select[name=sort]').val(query.sort);
        $('#ctools-export-ui-list-form input.ctools-auto-submit-click').trigger('click');
        event.preventDefault();
      });
    });
  }
};

})(jQuery, Drupal);

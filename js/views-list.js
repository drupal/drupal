/**
 * @file
 * Javascript related to the main view list.
 */
(function ($, Drupal) {

"use strict";

Drupal.behaviors.viewsUIList = {
  attach: function () {
    var $itemsTable = $('#ctools-export-ui-list-items thead').once('views-ajax-processed');
    var $itemsForm = $('#ctools-export-ui-list-form');
    if ($itemsTable.length) {
      $itemsTable.on('click', 'a', function (e) {
        e.preventDefault();
        var query = $.deparam.querystring(this.href);
        $itemsForm.find('select[name=order]').val(query.order);
        $itemsForm.find('select[name=sort]').val(query.sort);
        $itemsForm.find('input.ctools-auto-submit-click').trigger('click');
      });
    }
  },
  detach: function (context, settings, trigger) {
    if (trigger === 'unload') {
      var $itemsTable = $('#ctools-export-ui-list-items thead').removeOnce('views-ajax-processed');
      if ($itemsTable.length) {
        $itemsTable.off('click');
      }
    }
  }
};

})(jQuery, Drupal);

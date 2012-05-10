(function ($) {

"use strict";

Drupal.behaviors.filterStatus = {
  attach: function (context, settings) {
    var $context = $(context);
    $context.find('#filters-status-wrapper input.form-checkbox').once('filter-status', function () {
      var $checkbox = $(this);
      // Retrieve the tabledrag row belonging to this filter.
      var $row = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-weight')).closest('tr');
      // Retrieve the vertical tab belonging to this filter.
      var tab = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-settings')).data('verticalTab');

      // Bind click handler to this checkbox to conditionally show and hide the
      // filter's tableDrag row and vertical tab pane.
      $checkbox.bind('click.filterUpdate', function () {
        if ($checkbox.is(':checked')) {
          $row.show();
          if (tab) {
            tab.tabShow().updateSummary();
          }
        }
        else {
          $row.hide();
          if (tab) {
            tab.tabHide().updateSummary();
          }
        }
        // Restripe table after toggling visibility of table row.
        Drupal.tableDrag['filter-order'].restripeTable();
      });

      // Attach summary for configurable filters (only for screen-readers).
      if (tab) {
        tab.fieldset.drupalSetSummary(function (tabContext) {
          return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
        });
      }

      // Trigger our bound click handler to update elements to initial state.
      $checkbox.triggerHandler('click.filterUpdate');
    });
  }
};

})(jQuery);

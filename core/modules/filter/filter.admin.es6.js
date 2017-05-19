/**
 * @file
 * Attaches administration-specific behavior for the Filter module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Displays and updates the status of filters on the admin page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behaviors to the filter admin view.
   */
  Drupal.behaviors.filterStatus = {
    attach: function (context, settings) {
      var $context = $(context);
      $context.find('#filters-status-wrapper input.form-checkbox').once('filter-status').each(function () {
        var $checkbox = $(this);
        // Retrieve the tabledrag row belonging to this filter.
        var $row = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-weight')).closest('tr');
        // Retrieve the vertical tab belonging to this filter.
        var $filterSettings = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-settings'));
        var filterSettingsTab = $filterSettings.data('verticalTab');

        // Bind click handler to this checkbox to conditionally show and hide
        // the filter's tableDrag row and vertical tab pane.
        $checkbox.on('click.filterUpdate', function () {
          if ($checkbox.is(':checked')) {
            $row.show();
            if (filterSettingsTab) {
              filterSettingsTab.tabShow().updateSummary();
            }
            else {
              // On very narrow viewports, Vertical Tabs are disabled.
              $filterSettings.show();
            }
          }
          else {
            $row.hide();
            if (filterSettingsTab) {
              filterSettingsTab.tabHide().updateSummary();
            }
            else {
              // On very narrow viewports, Vertical Tabs are disabled.
              $filterSettings.hide();
            }
          }
          // Restripe table after toggling visibility of table row.
          Drupal.tableDrag['filter-order'].restripeTable();
        });

        // Attach summary for configurable filters (only for screen readers).
        if (filterSettingsTab) {
          filterSettingsTab.details.drupalSetSummary(function (tabContext) {
            return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
          });
        }

        // Trigger our bound click handler to update elements to initial state.
        $checkbox.triggerHandler('click.filterUpdate');
      });
    }
  };

})(jQuery, Drupal);

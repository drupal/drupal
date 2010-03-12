// $Id: filter.admin.js,v 1.2 2010/03/12 22:31:37 webchick Exp $

(function ($) {

/**
 * Shows the vertical tab pane.
 */
Drupal.verticalTab.prototype.tabShow = function () {
  // Display the tab.
  this.item.show();
  // Update .first marker for items. We need recurse from parent to retain the
  // actual DOM element order as jQuery implements sortOrder, but not as public
  // method.
  this.item.parent().children('.vertical-tab-button').removeClass('first')
    .filter(':visible:first').addClass('first');
  // Display the fieldset.
  this.fieldset.removeClass('filter-settings-hidden').show();
  // Focus this tab.
  this.focus();
  return this;
};

/**
 * Hides the vertical tab pane.
 */
Drupal.verticalTab.prototype.tabHide = function () {
  // Hide this tab.
  this.item.hide();
  // Update .first marker for items. We need recurse from parent to retain the
  // actual DOM element order as jQuery implements sortOrder, but not as public
  // method.
  this.item.parent().children('.vertical-tab-button').removeClass('first')
    .filter(':visible:first').addClass('first');
  // Hide the fieldset.
  this.fieldset.addClass('filter-settings-hidden').hide();
  // Focus the first visible tab (if there is one).
  var $firstTab = this.fieldset.siblings('.vertical-tabs-pane:not(.filter-settings-hidden):first');
  if ($firstTab.length) {
    $firstTab.data('verticalTab').focus();
  }
  return this;
};

Drupal.behaviors.filterStatus = {
  attach: function (context, settings) {
    $('#filters-status-wrapper input.form-checkbox', context).once('filter-status', function () {
      var $checkbox = $(this);
      // Retrieve the tabledrag row belonging to this filter.
      var $row = $('#' + $checkbox.attr('id').replace(/-status$/, '-weight'), context).closest('tr');
      // Retrieve the vertical tab belonging to this filter.
      var tab = $('#' + $checkbox.attr('id').replace(/-status$/, '-settings'), context).data('verticalTab');

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
        tab.fieldset.setSummary(function (tabContext) {
          return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
        });
      }

      // Trigger our bound click handler to update elements to initial state.
      $checkbox.triggerHandler('click.filterUpdate');
    });
  }
};

})(jQuery);

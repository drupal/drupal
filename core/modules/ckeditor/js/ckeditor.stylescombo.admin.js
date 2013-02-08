(function ($, Drupal) {

"use strict";

/**
 * Shows the "stylescombo" plugin settings only when the button is enabled.
 */
Drupal.behaviors.ckeditorStylesComboSettingsVisibility = {
  attach: function (context) {
    var $stylesComboVerticalTab = $('#edit-editor-settings-plugins-stylescombo').data('verticalTab');

    // Hide if the "Styles" button is disabled.
    if ($('.ckeditor-toolbar-disabled li[data-button-name="Styles"]').length === 1) {
      $stylesComboVerticalTab.tabHide();
    }

    // React to added/removed toolbar buttons.
    $(context)
      .find('.ckeditor-toolbar-active')
      .on('CKEditorToolbarChanged', function (e, action, button) {
        if (button === 'Styles') {
          if (action === 'added') {
            $stylesComboVerticalTab.tabShow();
          }
          else {
            $stylesComboVerticalTab.tabHide();
          }
        }
      });
  }
};

/**
 * Provides the summary for the "stylescombo" plugin settings vertical tab.
 */
Drupal.behaviors.ckeditorStylesComboSettingsSummary = {
  attach: function () {
    $('#edit-editor-settings-plugins-stylescombo').drupalSetSummary(function (context) {
      var styles = $.trim($('#edit-editor-settings-plugins-stylescombo-styles').val());
      if (styles.length === 0) {
        return Drupal.t('No styles configured');
      }
      else {
        var count = $.trim(styles).split("\n").length;
        return Drupal.t('@count styles configured', { '@count': count});
      }
    });
  }
};

})(jQuery, Drupal);

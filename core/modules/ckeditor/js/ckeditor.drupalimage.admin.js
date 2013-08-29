(function ($, Drupal, drupalSettings) {

"use strict";

/**
 * Shows the "drupalimage" plugin settings only when the button is enabled.
 */
Drupal.behaviors.ckeditorDrupalImageSettings = {
  attach: function (context) {
    var $context = $(context);
    var $drupalImageVerticalTab = $('#edit-editor-settings-plugins-drupalimage').data('verticalTab');

    // Hide if the "DrupalImage" button is disabled.
    if ($('.ckeditor-toolbar-disabled li[data-button-name="DrupalImage"]').length === 1) {
      $drupalImageVerticalTab.tabHide();
    }

    // React to added/removed toolbar buttons.
    $context
      .find('.ckeditor-toolbar-active')
      .on('CKEditorToolbarChanged.ckeditorDrupalImageSettings', function (e, action, button) {
        if (button === 'DrupalImage') {
          if (action === 'added') {
            $drupalImageVerticalTab.tabShow();
          }
          else {
            $drupalImageVerticalTab.tabHide();
          }
        }
      });
  }

};

/**
 * Provides the summary for the "drupalimage" plugin settings vertical tab.
 */
Drupal.behaviors.ckeditorDrupalImageSettingsSummary = {
  attach: function () {
    $('#edit-editor-settings-plugins-drupalimage').drupalSetSummary(function (context) {
      var root = 'input[name="editor[settings][plugins][drupalimage][image_upload]';
      var $status = $(root + '[status]"]');
      var $maxFileSize = $(root + '[max_size]"]');
      var $maxWidth = $(root + '[max_dimensions][width]"]');
      var $maxHeight = $(root + '[max_dimensions][height]"]');
      var $scheme = $(root + '[scheme]"]:checked');

      var maxFileSize = $maxFileSize.val() ? $maxFileSize.val() : $maxFileSize.attr('placeholder');
      var maxDimensions = ($maxWidth.val() && $maxHeight.val()) ? '(' + $maxWidth.val() + 'x' + $maxHeight.val() + ')' : '';

      if (!$status.is(':checked')) {
        return Drupal.t('Uploads disabled');
      }

      var output = '';
      output += Drupal.t('Uploads enabled, max size: @size @dimensions', { '@size': maxFileSize, '@dimensions': maxDimensions });
      if ($scheme.length) {
        output += '<br />' + $scheme.attr('data-label');
      }
      return output;
    });
  }
};

})(jQuery, Drupal, drupalSettings);

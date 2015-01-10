/**
 * @file
 * Provides JavaScript additions to the managed file field type.
 *
 * This file provides progress bar support (if available), popup windows for
 * file previews, and disabling of other file fields during Ajax uploads (which
 * prevents separate file fields from accidentally uploading files).
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Attach behaviors to managed file element upload fields.
   */
  Drupal.behaviors.fileValidateAutoAttach = {
    attach: function (context, settings) {
      var $context = $(context);
      var elements;

      function initFileValidation(selector) {
        $context.find(selector)
          .once('fileValidate')
          .on('change.fileValidate', {extensions: elements[selector]}, Drupal.file.validateExtension);
      }

      if (settings.file && settings.file.elements) {
        elements = settings.file.elements;
        Object.keys(elements).forEach(initFileValidation);
      }
    },
    detach: function (context, settings, trigger) {
      var $context = $(context);
      var elements;

      function removeFileValidation(selector) {
        $context.find(selector)
          .removeOnce('fileValidate')
          .off('change.fileValidate', Drupal.file.validateExtension);
      }

      if (trigger === 'unload' && settings.file && settings.file.elements) {
        elements = settings.file.elements;
        Object.keys(elements).forEach(removeFileValidation);
      }
    }
  };

  /**
   * Attach behaviors to managed file element upload fields.
   */
  Drupal.behaviors.fileAutoUpload = {
    attach: function (context) {
      $(context).find('input[type="file"]').once('auto-file-upload').on('change.autoFileUpload', Drupal.file.triggerUploadButton);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $(context).find('input[type="file"]').removeOnce('auto-file-upload').off('.autoFileUpload');
      }
    }
  };

  /**
   * Attach behaviors to the file upload and remove buttons.
   */
  Drupal.behaviors.fileButtons = {
    attach: function (context) {
      var $context = $(context);
      $context.find('.form-submit').on('mousedown', Drupal.file.disableFields);
      $context.find('.form-managed-file .form-submit').on('mousedown', Drupal.file.progressBar);
    },
    detach: function (context) {
      var $context = $(context);
      $context.find('.form-submit').off('mousedown', Drupal.file.disableFields);
      $context.find('.form-managed-file .form-submit').off('mousedown', Drupal.file.progressBar);
    }
  };

  /**
   * Attach behaviors to links within managed file elements.
   */
  Drupal.behaviors.filePreviewLinks = {
    attach: function (context) {
      $(context).find('div.form-managed-file .file a, .file-widget .file a').on('click', Drupal.file.openInNewWindow);
    },
    detach: function (context) {
      $(context).find('div.form-managed-file .file a, .file-widget .file a').off('click', Drupal.file.openInNewWindow);
    }
  };

  /**
   * File upload utility functions.
   */
  Drupal.file = Drupal.file || {
    /**
     * Client-side file input validation of file extensions.
     */
    validateExtension: function (event) {
      event.preventDefault();
      // Remove any previous errors.
      $('.file-upload-js-error').remove();

      // Add client side validation for the input[type=file].
      var extensionPattern = event.data.extensions.replace(/,\s*/g, '|');
      if (extensionPattern.length > 1 && this.value.length > 0) {
        var acceptableMatch = new RegExp('\\.(' + extensionPattern + ')$', 'gi');
        if (!acceptableMatch.test(this.value)) {
          var error = Drupal.t("The selected file %filename cannot be uploaded. Only files with the following extensions are allowed: %extensions.", {
            // According to the specifications of HTML5, a file upload control
            // should not reveal the real local path to the file that a user
            // has selected. Some web browsers implement this restriction by
            // replacing the local path with "C:\fakepath\", which can cause
            // confusion by leaving the user thinking perhaps Drupal could not
            // find the file because it messed up the file path. To avoid this
            // confusion, therefore, we strip out the bogus fakepath string.
            '%filename': this.value.replace('C:\\fakepath\\', ''),
            '%extensions': extensionPattern.replace(/\|/g, ', ')
          });
          $(this).closest('div.form-managed-file').prepend('<div class="messages messages--error file-upload-js-error" aria-live="polite">' + error + '</div>');
          this.value = '';
          // Cancel all other change event handlers.
          event.stopImmediatePropagation();
        }
      }
    },
    /**
     * Trigger the upload_button mouse event to auto-upload as a managed file.
     */
    triggerUploadButton: function (event) {
      $(event.target).closest('.form-managed-file').find('.form-submit').trigger('mousedown');
    },
    /**
     * Prevent file uploads when using buttons not intended to upload.
     */
    disableFields: function (event) {
      var $clickedButton = $(this);

      // Only disable upload fields for Ajax buttons.
      if (!$clickedButton.hasClass('ajax-processed')) {
        return;
      }

      // Check if we're working with an "Upload" button.
      var $enabledFields = [];
      if ($clickedButton.closest('div.form-managed-file').length > 0) {
        $enabledFields = $clickedButton.closest('div.form-managed-file').find('input.form-file');
      }

      // Temporarily disable upload fields other than the one we're currently
      // working with. Filter out fields that are already disabled so that they
      // do not get enabled when we re-enable these fields at the end of behavior
      // processing. Re-enable in a setTimeout set to a relatively short amount
      // of time (1 second). All the other mousedown handlers (like Drupal's Ajax
      // behaviors) are excuted before any timeout functions are called, so we
      // don't have to worry about the fields being re-enabled too soon.
      // @todo If the previous sentence is true, why not set the timeout to 0?
      var $fieldsToTemporarilyDisable = $('div.form-managed-file input.form-file').not($enabledFields).not(':disabled');
      $fieldsToTemporarilyDisable.prop('disabled', true);
      setTimeout(function () {
        $fieldsToTemporarilyDisable.prop('disabled', false);
      }, 1000);
    },
    /**
     * Add progress bar support if possible.
     */
    progressBar: function (event) {
      var $clickedButton = $(this);
      var $progressId = $clickedButton.closest('div.form-managed-file').find('input.file-progress');
      if ($progressId.length) {
        var originalName = $progressId.attr('name');

        // Replace the name with the required identifier.
        $progressId.attr('name', originalName.match(/APC_UPLOAD_PROGRESS|UPLOAD_IDENTIFIER/)[0]);

        // Restore the original name after the upload begins.
        setTimeout(function () {
          $progressId.attr('name', originalName);
        }, 1000);
      }
      // Show the progress bar if the upload takes longer than half a second.
      setTimeout(function () {
        $clickedButton.closest('div.form-managed-file').find('div.ajax-progress-bar').slideDown();
      }, 500);
    },
    /**
     * Open links to files within forms in a new window.
     */
    openInNewWindow: function (event) {
      event.preventDefault();
      $(this).attr('target', '_blank');
      window.open(this.href, 'filePreview', 'toolbar=0,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=500,height=550');
    }
  };

})(jQuery, Drupal);

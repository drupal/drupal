/**
 * @file
 * Attaches behavior for the Editor module.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Finds the text area field associated with the given text format selector.
   *
   * @param jQuery $formatSelector
   *   A text format selector DOM element.
   *
   * @return DOM
   *   The text area DOM element.
   */
  function findFieldForFormatSelector($formatSelector) {
    var field_id = $formatSelector.attr('data-editor-for');
    return $('#' + field_id).get(0);
  }

  /**
   * Changes the text editor on the text area for the given text format selector.
   *
   * @param jQuery $formatSelector
   *   A text format selector DOM element.
   * @param String activeFormatID
   *   The currently active text format; its associated text editor will be
   *   detached.
   * @param String newFormatID
   *   The text format we're changing to; its associated text editor will be
   *   attached.
   */
  function changeTextEditor($formatSelector, activeFormatID, newFormatID) {
    var field = findFieldForFormatSelector($formatSelector);
    // Detach the current editor (if any) and attach a new editor.
    if (drupalSettings.editor.formats[activeFormatID]) {
      Drupal.editorDetach(field, drupalSettings.editor.formats[activeFormatID]);
    }
    activeFormatID = newFormatID;
    if (drupalSettings.editor.formats[activeFormatID]) {
      Drupal.editorAttach(field, drupalSettings.editor.formats[activeFormatID]);
    }
    $formatSelector.attr('data-editor-active-text-format', newFormatID);
  }

  /**
   * Handles changes in text format.
   *
   * @param jQuery.Event event
   */
  function onTextFormatChange(event) {
    var $select = $(event.target);
    var activeFormatID = $select.attr('data-editor-active-text-format');
    var newFormatID = $select.val();

    // Prevent double-attaching if the change event is triggered manually.
    if (newFormatID === activeFormatID) {
      return;
    }

    // When changing to a text format that has a text editor associated
    // with it that supports content filtering, then first ask for
    // confirmation, because switching text formats might cause certain
    // markup to be stripped away.
    if (drupalSettings.editor.formats[newFormatID] && drupalSettings.editor.formats[newFormatID].editorSupportsContentFiltering) {
      var message = Drupal.t('Changing the text format to %text_format will permanently remove content that is not allowed in that text format.<br><br>Save your changes before switching the text format to avoid losing data.', {
        '%text_format': $select.find('option:selected').text()
      });
      var confirmationDialog = Drupal.dialog('<div>' + message + '</div>', {
        title: Drupal.t('Change text format?'),
        dialogClass: 'editor-change-text-format-modal',
        resizable: false,
        buttons: [
          {
            text: Drupal.t('Continue'),
            'class': 'button button--primary',
            click: function () {
              changeTextEditor($select, activeFormatID, newFormatID);
              confirmationDialog.close();
            }
          },
          {
            text: Drupal.t('Cancel'),
            'class': 'button',
            click: function () {
              // Restore the active format ID: cancel changing text format. We cannot
              // simply call event.preventDefault() because jQuery's change event is
              // only triggered after the change has already been accepted.
              $select.val(activeFormatID);
              confirmationDialog.close();
            }
          }
        ],
        // Prevent this modal from being closed without the user making a choice
        // as per http://stackoverflow.com/a/5438771.
        closeOnEscape: false,
        create: function () {
          $(this).parent().find('.ui-dialog-titlebar-close').remove();
        },
        beforeClose: false,
        close: function (event) {
          // Automatically destroy the DOM element that was used for the dialog.
          $(event.target).remove();
        }
      });

      confirmationDialog.showModal();
    }
    else {
      changeTextEditor($select, activeFormatID, newFormatID);
    }
  }

  /**
   * Initialize an empty object for editors to place their attachment code.
   */
  Drupal.editors = {};

  /**
   * Enables editors on text_format elements.
   */
  Drupal.behaviors.editor = {
    attach: function (context, settings) {
      // If there are no editor settings, there are no editors to enable.
      if (!settings.editor) {
        return;
      }

      $(context).find('.editor').once('editor', function () {
        var $this = $(this);
        var activeFormatID = $this.val();
        $this.attr('data-editor-active-text-format', activeFormatID);
        var field = findFieldForFormatSelector($this);

        // Directly attach this editor, if the text format is enabled.
        if (settings.editor.formats[activeFormatID]) {
          Drupal.editorAttach(field, settings.editor.formats[activeFormatID]);
        }

        // Attach onChange handler to text format selector element.
        if ($this.is('select')) {
          $this.on('change.editorAttach', onTextFormatChange);
        }
        // Detach any editor when the containing form is submitted.
        $this.parents('form').on('submit', function (event) {
          // Do not detach if the event was canceled.
          if (event.isDefaultPrevented()) {
            return;
          }
          // Detach the current editor (if any).
          if (settings.editor.formats[activeFormatID]) {
            Drupal.editorDetach(field, settings.editor.formats[activeFormatID], 'serialize');
          }
        });
      });
    },

    detach: function (context, settings, trigger) {
      var editors;
      // The 'serialize' trigger indicates that we should simply update the
      // underlying element with the new text, without destroying the editor.
      if (trigger === 'serialize') {
        // Removing the editor-processed class guarantees that the editor will
        // be reattached. Only do this if we're planning to destroy the editor.
        editors = $(context).find('.editor-processed');
      }
      else {
        editors = $(context).find('.editor').removeOnce('editor');
      }

      editors.each(function () {
        var $this = $(this);
        var activeFormatID = $this.val();
        var field = findFieldForFormatSelector($this);
        if (activeFormatID in settings.editor.formats) {
          Drupal.editorDetach(field, settings.editor.formats[activeFormatID], trigger);
        }
      });
    }
  };

  Drupal.editorAttach = function (field, format) {
    if (format.editor) {
      // HTML5 validation cannot ever work for WYSIWYG editors, because WYSIWYG
      // editors always hide the underlying textarea element, which prevents
      // browsers from putting the error message bubble in the right location.
      // Hence: disable HTML5 validation for this element.
      if ('required' in field.attributes) {
        field.setAttribute('data-editor-required', true);
        field.removeAttribute('required');
      }

      // Attach the text editor.
      Drupal.editors[format.editor].attach(field, format);

      // Ensures form.js' 'formUpdated' event is triggered even for changes that
      // happen within the text editor.
      Drupal.editors[format.editor].onChange(field, function () {
        $(field).trigger('formUpdated');
      });
    }
  };

  Drupal.editorDetach = function (field, format, trigger) {
    if (format.editor) {
      // Restore the HTML5 validation "required" attribute if it was removed in
      // Drupal.editorAttach().
      if ('data-editor-required' in field.attributes) {
        field.setAttribute('required', 'required');
        field.removeAttribute('data-editor-required');
      }

      Drupal.editors[format.editor].detach(field, format, trigger);
    }
  };

})(jQuery, Drupal, drupalSettings);

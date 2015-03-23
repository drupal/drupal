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
    var originalFormatID = activeFormatID;
    var field = findFieldForFormatSelector($formatSelector);
    // Detach the current editor (if any) and attach a new editor.
    if (drupalSettings.editor.formats[activeFormatID]) {
      Drupal.editorDetach(field, drupalSettings.editor.formats[activeFormatID]);
    }
    // When no text editor is currently active, stop tracking changes.
    else if (!drupalSettings.editor.formats[activeFormatID]) {
      $(field).off('.editor');
    }
    activeFormatID = newFormatID;

    // Attach the new text editor (if any).
    if (drupalSettings.editor.formats[activeFormatID]) {
      var format = drupalSettings.editor.formats[activeFormatID];
      filterXssWhenSwitching(field, format, originalFormatID, Drupal.editorAttach);
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
    var supportContentFiltering = drupalSettings.editor.formats[newFormatID] && drupalSettings.editor.formats[newFormatID].editorSupportsContentFiltering;
    // If there is no content yet, it's always safe to change the text format.
    var hasContent = findFieldForFormatSelector($select).value !== '';
    if (hasContent && supportContentFiltering) {
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

      $(context).find('.editor').once('editor').each(function () {
        var $this = $(this);
        var activeFormatID = $this.val();
        $this.attr('data-editor-active-text-format', activeFormatID);
        var field = findFieldForFormatSelector($this);

        // Directly attach this text editor, if the text format is enabled.
        if (settings.editor.formats[activeFormatID]) {
          // XSS protection for the current text format/editor is performed on the
          // server side, so we don't need to do anything special here.
          Drupal.editorAttach(field, settings.editor.formats[activeFormatID]);
        }
        // When there is no text editor for this text format, still track changes,
        // because the user has the ability to switch to some text editor, other-
        // wise this code would not be executed.
        else {
          $(field).on('change.editor keypress.editor', function () {
            field.setAttribute('data-editor-value-is-changed', 'true');
            // Just knowing that the value was changed is enough, stop tracking.
            $(field).off('.editor');
          });
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
        editors = $(context).find('.editor').findOnce('editor');
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

        // Keep track of changes, so we know what to do when switching text
        // formats and guaranteeing XSS protection.
        field.setAttribute('data-editor-value-is-changed', 'true');
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

      // Restore the original value if the user didn't make any changes yet.
      if (field.getAttribute('data-editor-value-is-changed') === 'false') {
        field.value = field.getAttribute('data-editor-value-original');
      }
    }
  };

  /**
   * Filter away XSS attack vectors when switching text formats.
   *
   * @param DOM field
   *   The textarea DOM element.
   * @param Object format
   *   The text format that's being activated, from drupalSettings.editor.formats.
   * @param String originalFormatID
   *   The text format ID of the original text format.
   * @param Function callback
   *   A callback to be called (with no parameters) after the field's value has
   *   been XSS filtered.
   */
  function filterXssWhenSwitching(field, format, originalFormatID, callback) {
    // A text editor that already is XSS-safe needs no additional measures.
    if (format.editor.isXssSafe) {
      callback(field, format);
    }
    // Otherwise, ensure XSS safety: let the server XSS filter this value.
    else {
      $.ajax({
        url: Drupal.url('editor/filter_xss/' + format.format),
        type: 'POST',
        data: {
          'value': field.value,
          'original_format_id': originalFormatID
        },
        dataType: 'json',
        success: function (xssFilteredValue) {
          // If the server returns false, then no XSS filtering is needed.
          if (xssFilteredValue !== false) {
            field.value = xssFilteredValue;
          }
          callback(field, format);
        }
      });
    }
  }

})(jQuery, Drupal, drupalSettings);

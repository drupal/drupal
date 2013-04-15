/**
 * @file
 * Text editor-based Create.js widget for processed text content in Drupal.
 *
 * Depends on editor.module. Works with any (WYSIWYG) editor that implements the
 * editor.js API, including the optional attachInlineEditor() and onChange()
 * methods.
 * For example, assuming that a hypothetical editor's name was "Magical Editor"
 * and its editor.js API implementation lived at Drupal.editors.magical, this
 * JavaScript would use:
 *  - Drupal.editors.magical.attachInlineEditor()
 *  - Drupal.editors.magical.onChange()
 *  - Drupal.editors.magical.detach()
 */
(function (jQuery, Drupal, drupalSettings) {

"use strict";

// @todo D8: use jQuery UI Widget bridging.
// @see http://drupal.org/node/1874934#comment-7124904
jQuery.widget('Midgard.editor', jQuery.Midgard.direct, {

  textFormat: null,
  textFormatHasTransformations: null,
  textEditor: null,

  /**
   * Implements Create.editWidget.getEditUISettings.
   */
  getEditUISettings: function () {
    return { padding: true, unifiedToolbar: true, fullWidthToolbar: true };
  },

  /**
   * Implements jQuery.widget._init.
   *
   * @todo D8: Remove this.
   * @see http://drupal.org/node/1874934
   */
  _init: function () {},

  /**
   * Implements Create.editWidget._initialize.
   */
  _initialize: function () {
    var propertyID = Drupal.edit.util.calcPropertyID(this.options.entity, this.options.property);
    var metadata = Drupal.edit.metadataCache[propertyID].custom;

    this.textFormat = drupalSettings.editor.formats[metadata.format];
    this.textFormatHasTransformations = metadata.formatHasTransformations;
    this.textEditor = Drupal.editors[this.textFormat.editor];
  },

  /**
   * Implements Create.editWidget.stateChange.
   */
  stateChange: function (from, to) {
    var that = this;
    switch (to) {
      case 'inactive':
        break;

      case 'candidate':
        // Detach the text editor when entering the 'candidate' state from one
        // of the states where it could have been attached.
        if (from !== 'inactive' && from !== 'highlighted') {
            this.textEditor.detach(this.element.get(0), this.textFormat);
        }
        break;

      case 'highlighted':
        break;

      case 'activating':
        // When transformation filters have been been applied to the processed
        // text of this field, then we'll need to load a re-processed version of
        // it without the transformation filters.
        if (this.textFormatHasTransformations) {
          var propertyID = Drupal.edit.util.calcPropertyID(this.options.entity, this.options.property);
          this._getUntransformedText(propertyID, this.element, function (untransformedText) {
            that.element.html(untransformedText);
            that.options.activated();
          });
        }
        // When no transformation filters have been applied: start WYSIWYG
        // editing immediately!
        else {
          this.options.activated();
        }
        break;

      case 'active':
        this.textEditor.attachInlineEditor(
          this.element.get(0),
          this.textFormat,
          this.toolbarView.getMainWysiwygToolgroupId(),
          this.toolbarView.getFloatedWysiwygToolgroupId()
        );
        // Set the state to 'changed' whenever the content has changed.
        this.textEditor.onChange(this.element.get(0), function (html) {
          that.options.changed(html);
        });
        break;

      case 'changed':
        break;

      case 'saving':
        break;

      case 'saved':
        break;

      case 'invalid':
        break;
    }
  },

  /**
   * Loads untransformed text for a given property.
   *
   * More accurately: it re-processes processed text to exclude transformation
   * filters used by the text format.
   *
   * @param String propertyID
   *   A property ID that uniquely identifies the given property.
   * @param jQuery $editorElement
   *   The property's PropertyEditor DOM element.
   * @param Function callback
   *   A callback function that will receive the untransformed text.
   *
   * @see \Drupal\editor\Ajax\GetUntransformedTextCommand
   */
  _getUntransformedText: function (propertyID, $editorElement, callback) {
    // Create a Drupal.ajax instance to load the form.
    Drupal.ajax[propertyID] = new Drupal.ajax(propertyID, $editorElement, {
      url: Drupal.edit.util.buildUrl(propertyID, drupalSettings.editor.getUntransformedTextURL),
      event: 'editor-internal.editor',
      submit: { nocssjs : true },
      progress: { type : null } // No progress indicator.
    });
    // Implement a scoped editorGetUntransformedText AJAX command: calls the
    // callback.
    Drupal.ajax[propertyID].commands.editorGetUntransformedText = function(ajax, response, status) {
      callback(response.data);
      // Delete the Drupal.ajax instance that called this very function.
      delete Drupal.ajax[propertyID];
      $editorElement.off('editor-internal.editor');
    };
    // This will ensure our scoped editorGetUntransformedText AJAX command
    // gets called.
    $editorElement.trigger('editor-internal.editor');
  }

});

})(jQuery, Drupal, drupalSettings);

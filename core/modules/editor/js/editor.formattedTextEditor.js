/**
 * @file
 * Text editor-based in-place editor for formatted text content in Drupal.
 *
 * Depends on editor.module. Works with any (WYSIWYG) editor that implements the
 * editor.js API, including the optional attachInlineEditor() and onChange()
 * methods.
 * For example, assuming that a hypothetical editor's name was "Magical Editor"
 * and its editor.js API implementation lived at Drupal.editors.magical, this
 * JavaScript would use:
 *  - Drupal.editors.magical.attachInlineEditor()
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.quickedit.editors.editor = Drupal.quickedit.EditorView.extend({

    // The text format for this field.
    textFormat: null,

    // Indicates whether this text format has transformations.
    textFormatHasTransformations: null,

    // Stores a reference to the text editor object for this field.
    textEditor: null,

    // Stores the textual DOM element that is being in-place edited.
    $textElement: null,

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      Drupal.quickedit.EditorView.prototype.initialize.call(this, options);

      var metadata = Drupal.quickedit.metadata.get(this.fieldModel.get('fieldID'), 'custom');
      this.textFormat = drupalSettings.editor.formats[metadata.format];
      this.textFormatHasTransformations = metadata.formatHasTransformations;
      this.textEditor = Drupal.editors[this.textFormat.editor];

      // Store the actual value of this field. We'll need this to restore the
      // original value when the user discards his modifications.
      this.$textElement = this.$el.find('.field-item:first');
      this.model.set('originalValue', this.$textElement.html());
    },

    /**
     * {@inheritdoc}
     */
    getEditedElement: function () {
      return this.$textElement;
    },

    /**
     * {@inheritdoc}
     */
    stateChange: function (fieldModel, state) {
      var editorModel = this.model;
      var from = fieldModel.previous('state');
      var to = state;
      switch (to) {
        case 'inactive':
          break;

        case 'candidate':
          // Detach the text editor when entering the 'candidate' state from one
          // of the states where it could have been attached.
          if (from !== 'inactive' && from !== 'highlighted') {
            this.textEditor.detach(this.$textElement.get(0), this.textFormat);
          }
          // A field model's editor view revert() method is invoked when an
          // 'active' field becomes a 'candidate' field. But, in the case of
          // this in-place editor, the content will have been *replaced* if the
          // text format has transformation filters. Therefore, if we stop
          // in-place editing this entity, revert explicitly.
          if (from === 'active' && this.textFormatHasTransformations) {
            this.revert();
          }
          if (from === 'invalid') {
            this.removeValidationErrors();
          }
          break;

        case 'highlighted':
          break;

        case 'activating':
          // When transformation filters have been been applied to the formatted
          // text of this field, then we'll need to load a re-formatted version
          // of it without the transformation filters.
          if (this.textFormatHasTransformations) {
            var $textElement = this.$textElement;
            this._getUntransformedText(function (untransformedText) {
              $textElement.html(untransformedText);
              fieldModel.set('state', 'active');
            });
          }
          // When no transformation filters have been applied: start WYSIWYG
          // editing immediately!
          else {
            // Defer updating the model until the current state change has
            // propagated, to not trigger a nested state change event.
            _.defer(function () {
              fieldModel.set('state', 'active');
            });
          }
          break;

        case 'active':
          var textElement = this.$textElement.get(0);
          var toolbarView = fieldModel.toolbarView;
          this.textEditor.attachInlineEditor(
            textElement,
            this.textFormat,
            toolbarView.getMainWysiwygToolgroupId(),
            toolbarView.getFloatedWysiwygToolgroupId()
          );
          // Set the state to 'changed' whenever the content has changed.
          this.textEditor.onChange(textElement, function (htmlText) {
            editorModel.set('currentValue', htmlText);
            fieldModel.set('state', 'changed');
          });
          break;

        case 'changed':
          break;

        case 'saving':
          if (from === 'invalid') {
            this.removeValidationErrors();
          }
          this.save();
          break;

        case 'saved':
          break;

        case 'invalid':
          this.showValidationErrors();
          break;
      }
    },

    /**
     * {@inheritdoc}
     */
    getQuickEditUISettings: function () {
      return { padding: true, unifiedToolbar: true, fullWidthToolbar: true, popup: false };
    },

    /**
     * {@inheritdoc}
     */
    revert: function () {
      this.$textElement.html(this.model.get('originalValue'));
    },

    /**
     * Loads untransformed text for this field.
     *
     * More accurately: it re-filters formatted text to exclude transformation
     * filters used by the text format.
     *
     * @param Function callback
     *   A callback function that will receive the untransformed text.
     *
     * @see \Drupal\editor\Ajax\GetUntransformedTextCommand
     */
    _getUntransformedText: function (callback) {
      var fieldID = this.fieldModel.get('fieldID');

      // Create a Drupal.ajax instance to load the form.
      var textLoaderAjax = new Drupal.ajax(fieldID, this.$el, {
        url: Drupal.quickedit.util.buildUrl(fieldID, Drupal.url('editor/!entity_type/!id/!field_name/!langcode/!view_mode')),
        event: 'editor-internal.editor',
        submit: { nocssjs: true },
        progress: { type: null } // No progress indicator.
      });

      // Implement a scoped editorGetUntransformedText AJAX command: calls the
      // callback.
      textLoaderAjax.commands.editorGetUntransformedText = function (ajax, response, status) {
        callback(response.data);
      };

      // This will ensure our scoped editorGetUntransformedText AJAX command
      // gets called.
      this.$el.trigger('editor-internal.editor');
    }

  });

})(jQuery, Drupal, drupalSettings);

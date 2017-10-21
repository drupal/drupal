/**
 * @file
 * ContentEditable-based in-place editor for plain text content.
 */

(function ($, _, Drupal) {
  Drupal.quickedit.editors.plain_text = Drupal.quickedit.EditorView.extend(/** @lends Drupal.quickedit.editors.plain_text# */{

    /**
     * Stores the textual DOM element that is being in-place edited.
     */
    $textElement: null,

    /**
     * @constructs
     *
     * @augments Drupal.quickedit.EditorView
     *
     * @param {object} options
     *   Options for the plain text editor.
     */
    initialize(options) {
      Drupal.quickedit.EditorView.prototype.initialize.call(this, options);

      const editorModel = this.model;
      const fieldModel = this.fieldModel;

      // Store the original value of this field. Necessary for reverting
      // changes.
      const $fieldItems = this.$el.find('.quickedit-field');
      const $textElement = $fieldItems.length ? $fieldItems.eq(0) : this.$el;
      this.$textElement = $textElement;
      editorModel.set('originalValue', $.trim(this.$textElement.text()));

      // Sets the state to 'changed' whenever the value changes.
      let previousText = editorModel.get('originalValue');
      $textElement.on('keyup paste', (event) => {
        const currentText = $.trim($textElement.text());
        if (previousText !== currentText) {
          previousText = currentText;
          editorModel.set('currentValue', currentText);
          fieldModel.set('state', 'changed');
        }
      });
    },

    /**
     * @inheritdoc
     *
     * @return {jQuery}
     *   The text element for the plain text editor.
     */
    getEditedElement() {
      return this.$textElement;
    },

    /**
     * @inheritdoc
     *
     * @param {object} fieldModel
     *   The field model that holds the state.
     * @param {string} state
     *   The state to change to.
     * @param {object} options
     *   State options, if needed by the state change.
     */
    stateChange(fieldModel, state, options) {
      const from = fieldModel.previous('state');
      const to = state;
      switch (to) {
        case 'inactive':
          break;

        case 'candidate':
          if (from !== 'inactive') {
            this.$textElement.removeAttr('contenteditable');
          }
          if (from === 'invalid') {
            this.removeValidationErrors();
          }
          break;

        case 'highlighted':
          break;

        case 'activating':
          // Defer updating the field model until the current state change has
          // propagated, to not trigger a nested state change event.
          _.defer(() => {
            fieldModel.set('state', 'active');
          });
          break;

        case 'active':
          this.$textElement.attr('contenteditable', 'true');
          break;

        case 'changed':
          break;

        case 'saving':
          if (from === 'invalid') {
            this.removeValidationErrors();
          }
          this.save(options);
          break;

        case 'saved':
          break;

        case 'invalid':
          this.showValidationErrors();
          break;
      }
    },

    /**
     * @inheritdoc
     *
     * @return {object}
     *   A settings object for the quick edit UI.
     */
    getQuickEditUISettings() {
      return { padding: true, unifiedToolbar: false, fullWidthToolbar: false, popup: false };
    },

    /**
     * @inheritdoc
     */
    revert() {
      this.$textElement.html(this.model.get('originalValue'));
    },

  });
}(jQuery, _, Drupal));

/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalimagealternativetextui contextualballoon componentfactory imagealternativetextformview missingalternativetextview imagetextalternativeui imagealternativetext */

/**
 * @module drupalImage/imagealternativetext/drupalimagealternativetextui
 */

import { Plugin, icons } from 'ckeditor5/src/core';
import {
  ButtonView,
  ContextualBalloon,
  clickOutsideHandler,
} from 'ckeditor5/src/ui';
import {
  repositionContextualBalloon,
  getBalloonPositionData,
} from '@ckeditor/ckeditor5-image/src/image/ui/utils';
import ImageAlternativeTextFormView from './ui/imagealternativetextformview';
import MissingAlternativeTextView from './ui/missingalternativetextview';

/**
 * The Drupal-specific image alternative text UI plugin.
 *
 * This plugin is based on a version of the upstream alternative text UI plugin.
 * This override enhances the UI with a new form element which allows marking
 * images explicitly as decorative. This plugin also provides a UI component
 * that can be displayed on images that are missing alternative text.
 *
 * The logic related to visibility, positioning, and keystrokes are unchanged
 * from the upstream implementation.
 *
 * The plugin uses the contextual balloon.
 *
 * @see module:image/imagetextalternative/imagetextalternativeui~ImageTextAlternativeUI
 * @see module:ui/panel/balloon/contextualballoon~ContextualBalloon
 *
 * @extends module:core/plugin~Plugin
 *
 * @internal
 */
export default class DrupalImageAlternativeTextUi extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [ContextualBalloon];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageTextAlternativeUI';
  }

  /**
   * @inheritdoc
   */
  init() {
    this._createButton();
    this._createForm();
    this._createMissingAltTextComponent();

    if (this.editor.plugins.has('ImageUploadEditing')) {
      const imageUploadEditing = this.editor.plugins.get('ImageUploadEditing');
      const imageUtils = this.editor.plugins.get('ImageUtils');
      imageUploadEditing.on('uploadComplete', () => {
        // Show form after upload if there's image widget in the current
        // selection.
        if (
          imageUtils.getClosestSelectedImageWidget(
            this.editor.editing.view.document.selection,
          )
        ) {
          this._showForm();
        }
      });
    }
  }

  /**
   * Creates a missing alt text view which can be displayed within image widgets
   * where the image is missing alt text.
   *
   * The component is registered in the editor component factory.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createMissingAltTextComponent() {
    this.editor.ui.componentFactory.add(
      'drupalImageAlternativeTextMissing',
      (locale) => {
        const view = new MissingAlternativeTextView(locale);
        view.listenTo(view.button, 'execute', () => {
          // If the form is already in the balloon, it needs to be removed to
          // avoid having multiple instances of the form in the balloon. This
          // happens only in the edge case where this event is executed while
          // the form is still in the balloon.
          if (this._isInBalloon) {
            this._balloon.remove(this._form);
          }
          this._showForm();
        });
        view.listenTo(this.editor.ui, 'update', () => {
          view.set({ isVisible: !this._isVisible || !view.isSelected });
        });
        return view;
      },
    );
  }

  /**
   * @inheritdoc
   */
  destroy() {
    super.destroy();

    // Destroy created UI components as they are not automatically destroyed
    // @see https://github.com/ckeditor/ckeditor5/issues/1341
    this._form.destroy();
  }

  /**
   * Creates a button showing the balloon panel for changing the image text
   * alternative and registers it in the editor component factory.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createButton() {
    const editor = this.editor;
    editor.ui.componentFactory.add('drupalImageAlternativeText', (locale) => {
      const command = editor.commands.get('imageTextAlternative');
      const view = new ButtonView(locale);

      view.set({
        label: Drupal.t('Change image alternative text'),
        icon: icons.lowVision,
        tooltip: true,
      });

      view.bind('isEnabled').to(command, 'isEnabled');

      this.listenTo(view, 'execute', () => {
        this._showForm();
      });

      return view;
    });
  }

  /**
   * Creates the text alternative form view.
   *
   * @private
   */
  _createForm() {
    const editor = this.editor;
    const view = editor.editing.view;
    const viewDocument = view.document;
    const imageUtils = editor.plugins.get('ImageUtils');

    /**
     * The contextual balloon plugin instance.
     *
     * @private
     * @member {module:ui/panel/balloon/contextualballoon~ContextualBalloon}
     */
    this._balloon = this.editor.plugins.get('ContextualBalloon');

    /**
     * A form used for changing the `alt` text value.
     *
     * @member {module:drupalImage/imagetextalternative/ui/imagealternativetextformview~ImageAlternativeTextFormView}
     */
    this._form = new ImageAlternativeTextFormView(editor.locale);

    // Render the form so its #element is available for clickOutsideHandler.
    this._form.render();

    this.listenTo(this._form, 'submit', () => {
      editor.execute('imageTextAlternative', {
        newValue: this._form.decorativeToggle.isOn
          ? ''
          : this._form.labeledInput.fieldView.element.value,
      });

      this._hideForm(true);
    });

    this.listenTo(this._form, 'cancel', () => {
      this._hideForm(true);
    });

    // Reposition the toolbar when the decorative toggle is executed because
    // it has an impact on the form size.
    this.listenTo(this._form.decorativeToggle, 'execute', () => {
      repositionContextualBalloon(editor);
    });

    // Close the form on Esc key press.
    this._form.keystrokes.set('Esc', (data, cancel) => {
      this._hideForm(true);
      cancel();
    });

    // Reposition the balloon or hide the form if an image widget is no longer
    // selected.
    this.listenTo(editor.ui, 'update', () => {
      if (!imageUtils.getClosestSelectedImageWidget(viewDocument.selection)) {
        this._hideForm(true);
      } else if (this._isVisible) {
        repositionContextualBalloon(editor);
      }
    });

    // Close on click outside of balloon panel element.
    clickOutsideHandler({
      emitter: this._form,
      activator: () => this._isVisible,
      contextElements: [this._balloon.view.element],
      callback: () => this._hideForm(),
    });
  }

  /**
   * Shows the form in the balloon.
   *
   * @private
   */
  _showForm() {
    if (this._isVisible) {
      return;
    }

    const editor = this.editor;
    const command = editor.commands.get('imageTextAlternative');
    const decorativeToggle = this._form.decorativeToggle;
    const labeledInput = this._form.labeledInput;

    this._form.disableCssTransitions();

    if (!this._isInBalloon) {
      this._balloon.add({
        view: this._form,
        position: getBalloonPositionData(editor),
      });
    }

    decorativeToggle.isOn = command.value === '';

    // Make sure that each time the panel shows up, the field remains in sync
    // with the value of the command. If the user typed in the input, then
    // canceled the balloon (`labeledInput#value` stays unaltered) and re-opened
    // it without changing the value of the command, they would see the old
    // value instead of the actual value of the command.
    // https://github.com/ckeditor/ckeditor5-image/issues/114
    labeledInput.fieldView.element.value = command.value || '';
    labeledInput.fieldView.value = labeledInput.fieldView.element.value;

    if (!decorativeToggle.isOn) {
      labeledInput.fieldView.select();
    } else {
      decorativeToggle.focus();
    }

    this._form.enableCssTransitions();
  }

  /**
   * Removes the form from the balloon.
   *
   * @param {Boolean} [focusEditable=false]
   *   Controls whether the editing view is focused afterwards.
   *
   * @private
   */
  _hideForm(focusEditable) {
    if (!this._isInBalloon) {
      return;
    }

    // Blur the input element before removing it from DOM to prevent issues in
    // some browsers.
    // See https://github.com/ckeditor/ckeditor5/issues/1501.
    if (this._form.focusTracker.isFocused) {
      this._form.saveButtonView.focus();
    }

    this._balloon.remove(this._form);

    if (focusEditable) {
      this.editor.editing.view.focus();
    }
  }

  /**
   * Returns `true` when the form is the visible view in the balloon.
   *
   * @type {Boolean}
   *
   * @private
   */
  get _isVisible() {
    return this._balloon.visibleView === this._form;
  }

  /**
   * Returns `true` when the form is in the balloon.
   *
   * @type {Boolean}
   *
   * @private
   */
  get _isInBalloon() {
    return this._balloon.hasView(this._form);
  }
}

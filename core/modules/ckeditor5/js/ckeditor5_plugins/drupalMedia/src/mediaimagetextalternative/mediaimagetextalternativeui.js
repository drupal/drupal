/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore imagetextalternative mediaimagetextalternative */
/* cspell:ignore mediaimagetextalternativeediting textalternativeformview */

import { Plugin } from 'ckeditor5/src/core';
import { IconLowVision } from '@ckeditor/ckeditor5-icons';
import {
  ButtonView,
  ContextualBalloon,
  clickOutsideHandler,
} from 'ckeditor5/src/ui';

import { getClosestSelectedDrupalMediaWidget, isDrupalMedia } from '../utils';
import {
  getBalloonPositionData,
  repositionContextualBalloon,
} from '../ui/utils';

import TextAlternativeFormView from './ui/textalternativeformview';

/**
 * The media image text alternative UI plugin.
 *
 * @see https://github.com/ckeditor/ckeditor5/blob/master/packages/ckeditor5-image/src/imagetextalternative/imagetextalternativeui.js
 */
export default class MediaImageTextAlternativeUi extends Plugin {
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
    return 'MediaImageTextAlternativeUi';
  }

  /**
   * @inheritdoc
   */
  init() {
    this._createButton();
    this._createForm();
  }

  /**
   * @inheritdoc
   */
  destroy() {
    super.destroy();
    this._form.destroy();
  }

  /**
   * Creates a button showing the balloon panel for changing the image text
   * alternative and registers it in the editor ComponentFactory.
   */
  _createButton() {
    const editor = this.editor;

    editor.ui.componentFactory.add('mediaImageTextAlternative', (locale) => {
      const command = editor.commands.get('mediaImageTextAlternative');
      const view = new ButtonView(locale);

      view.set({
        label: Drupal.t('Override media image alternative text'),
        icon: IconLowVision,
        tooltip: true,
      });

      view.bind('isVisible').to(command, 'isEnabled');

      this.listenTo(view, 'execute', () => {
        this._showForm();
      });

      return view;
    });
  }

  /**
   * Creates the {@link module:image/imagetextalternative/ui/textalternativeformview~TextAlternativeFormView}
   * form.
   *
   * @private
   */
  _createForm() {
    const editor = this.editor;
    const view = editor.editing.view;
    const viewDocument = view.document;

    /**
     * The contextual balloon plugin instance.
     */
    this._balloon = this.editor.plugins.get('ContextualBalloon');

    /**
     * A form containing a textarea and buttons, used to change the `alt` text value.
     */
    this._form = new TextAlternativeFormView(editor.locale);

    // Render the form so its #element is available for clickOutsideHandler.
    this._form.render();

    this.listenTo(this._form, 'submit', () => {
      editor.execute('mediaImageTextAlternative', {
        // The "decorative toggle" allows users to opt-in to empty alt
        // attributes for the very rare edge cases where that is valid. This is
        // indicated by specifying two double quotes as the alternative text.
        // See https://www.w3.org/WAI/tutorials/images/decorative .
        newValue: this._form.decorativeToggle.isOn
          ? '""'
          : this._form.labeledInput.fieldView.element.value,
      });

      this._hideForm(true);
    });

    this.listenTo(this._form, 'cancel', () => {
      this._hideForm(true);
    });

    // Close the form on Esc key press.
    this._form.keystrokes.set('Esc', (data, cancel) => {
      this._hideForm(true);
      cancel();
    });

    // Reposition the balloon or hide the form if a media widget is no longer
    // selected.
    this.listenTo(editor.ui, 'update', () => {
      if (!getClosestSelectedDrupalMediaWidget(viewDocument.selection)) {
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
   * Shows the form in a balloon.
   */
  _showForm() {
    if (this._isVisible) {
      return;
    }
    const editor = this.editor;
    const command = editor.commands.get('mediaImageTextAlternative');
    const decorativeToggle = this._form.decorativeToggle;
    const metadataRepository = editor.plugins.get(
      'DrupalMediaMetadataRepository',
    );
    const labeledInput = this._form.labeledInput;

    this._form.disableCssTransitions();

    if (!this._isInBalloon) {
      this._balloon.add({
        view: this._form,
        position: getBalloonPositionData(editor),
      });
    }

    // This implementation, populating double quotes, differs from drupalImage.
    // In drupalImage, an image either has alt text or it is decorative, so the
    // 'decorative' state can be represented by an empty string. In drupalMedia,
    // an image can inherit alt text from the media entity (represented by an
    // empty string), can have overridden alt text (represented by user-entered
    // text), or can be designated decorative (represented by double quotes).
    decorativeToggle.isOn = command.value === '""';

    // Make sure that each time the panel shows up, the field remains in sync with the value of
    // the command. If the user typed in the input, then canceled the balloon (`labeledInput#value`
    // stays unaltered) and re-opened it without changing the value of the command, they would see the
    // old value instead of the actual value of the command.
    // https://github.com/ckeditor/ckeditor5-image/issues/114
    labeledInput.fieldView.element.value = command.value || '';
    labeledInput.fieldView.value = labeledInput.fieldView.element.value;

    this._form.defaultAltText = '';
    const modelElement = editor.model.document.selection.getSelectedElement();

    // Make sure that each time the panel shows up, the default alt text remains
    // in sync with the value from the metadata repository.
    if (isDrupalMedia(modelElement)) {
      metadataRepository
        .getMetadata(modelElement)
        .then((metadata) => {
          this._form.defaultAltText = metadata.imageSourceMetadata
            ? metadata.imageSourceMetadata.alt
            : '';
          labeledInput.infoText = Drupal.t(
            `Leave blank to use the default alternative text: "${this._form.defaultAltText}".`,
          );
        })
        .catch((e) => {
          // There isn't any UI indication for errors because this should be
          // always called after the Drupal Media has been upcast, which would
          // already display an error in the UI.
          // @see module:drupalMedia/mediaimagetextalternative/mediaimagetextalternativeediting~MediaImageTextAlternativeEditing
          console.warn(e.toString());
        });
    }

    this._form.enableCssTransitions();
  }

  /**
   * Removes the {@link #_form} from the {@link #_balloon}.
   *
   * @param {Boolean} [focusEditable=false] Controls whether the editing view is focused afterwards.
   * @private
   */
  _hideForm(focusEditable) {
    if (!this._isInBalloon) {
      return;
    }

    // Blur the input element before removing it from DOM to prevent issues in some browsers.
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
   */
  get _isVisible() {
    return this._balloon.visibleView === this._form;
  }

  /**
   * Returns `true` when the form is in the balloon.
   *
   * @type {Boolean}
   */
  get _isInBalloon() {
    return this._balloon.hasView(this._form);
  }
}

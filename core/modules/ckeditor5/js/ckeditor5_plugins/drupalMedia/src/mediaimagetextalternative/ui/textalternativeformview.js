/* eslint-disable import/no-extraneous-dependencies */

// cspell:ignore focusables

import {
  ButtonView,
  FocusCycler,
  LabeledFieldView,
  View,
  ViewCollection,
  createLabeledInputText,
  injectCssTransitionDisabler,
  submitHandler,
} from 'ckeditor5/src/ui';
import { FocusTracker, KeystrokeHandler } from 'ckeditor5/src/utils';
import { icons } from 'ckeditor5/src/core';

// cspell:ignore focusables

export default class TextAlternativeFormView extends View {
  /**
   * @inheritdoc
   */
  constructor(locale) {
    super(locale);

    /**
     * Tracks information about the DOM focus in the form.
     */
    this.focusTracker = new FocusTracker();

    /**
     * An instance of the KeystrokeHandler.
     */
    this.keystrokes = new KeystrokeHandler();

    /**
     * An input with a label.
     */
    this.labeledInput = this._createLabeledInputView();

    /**
     * A button used to submit the form.
     */
    this.saveButtonView = this._createButton(
      Drupal.t('Save'),
      icons.check,
      'ck-button-save',
    );
    this.saveButtonView.type = 'submit';

    /**
     * A button used to cancel the form.
     */
    this.cancelButtonView = this._createButton(
      Drupal.t('Cancel'),
      icons.cancel,
      'ck-button-cancel',
      'cancel',
    );

    /**
     * A collection of views which can be focused in the form.
     */
    this._focusables = new ViewCollection();

    /**
     * Helps cycling over focusables in the form.
     */
    this._focusCycler = new FocusCycler({
      focusables: this._focusables,
      focusTracker: this.focusTracker,
      keystrokeHandler: this.keystrokes,
      actions: {
        // Navigate form fields backwards using the Shift + Tab keystroke.
        focusPrevious: 'shift + tab',

        // Navigate form fields forwards using the Tab key.
        focusNext: 'tab',
      },
    });

    this.setTemplate({
      tag: 'form',

      attributes: {
        class: ['ck', 'ck-text-alternative-form', 'ck-responsive-form'],
        tabindex: '-1',
      },

      children: [this.labeledInput, this.saveButtonView, this.cancelButtonView],
    });

    injectCssTransitionDisabler(this);
  }

  /**
   * @inheritdoc
   */
  render() {
    super.render();

    this.keystrokes.listenTo(this.element);

    submitHandler({ view: this });

    [this.labeledInput, this.saveButtonView, this.cancelButtonView].forEach(
      (v) => {
        // Register the view as focusable.
        this._focusables.add(v);

        // Register the view in the focus tracker.
        this.focusTracker.add(v.element);
      },
    );
  }

  /**
   * Creates the button view.
   *
   * @param {String} label
   *   The button label
   * @param {String} icon
   *   The button's icon.
   * @param {String} className
   *   The additional button CSS class name.
   * @param {String} [eventName]
   *   The event name that the ButtonView#execute event will be delegated to.
   * @return {module:ui/view~View}
   *   The button view instance.
   */
  _createButton(label, icon, className, eventName) {
    const button = new ButtonView(this.locale);

    button.set({
      label,
      icon,
      tooltip: true,
    });

    button.extendTemplate({
      attributes: {
        class: className,
      },
    });

    if (eventName) {
      button.delegate('execute').to(this, eventName);
    }

    return button;
  }

  /**
   * Creates an input with a label.
   *
   * @return {module:ui/view~View}
   *   Labeled field view instance.
   */
  _createLabeledInputView() {
    const labeledInput = new LabeledFieldView(
      this.locale,
      createLabeledInputText,
    );

    labeledInput.label = Drupal.t('Override text alternative');

    return labeledInput;
  }
}

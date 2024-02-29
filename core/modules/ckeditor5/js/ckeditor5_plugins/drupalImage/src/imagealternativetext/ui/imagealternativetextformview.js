/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore focustracker keystrokehandler labeledfield labeledfieldview buttonview viewcollection focusables focuscycler switchbuttonview imagealternativetextformview imagealternativetext */

/**
 * @module drupalImage/imagealternativetext/ui/imagealternativetextformview
 */

import {
  ButtonView,
  FocusCycler,
  LabeledFieldView,
  SwitchButtonView,
  View,
  ViewCollection,
  createLabeledInputText,
  injectCssTransitionDisabler,
  submitHandler,
} from 'ckeditor5/src/ui';
import { FocusTracker, KeystrokeHandler } from 'ckeditor5/src/utils';
import { icons } from 'ckeditor5/src/core';

/**
 * A class rendering alternative text form view.
 *
 * @extends module:ui/view~View
 *
 * @internal
 */
export default class ImageAlternativeTextFormView extends View {
  /**
   * @inheritdoc
   */
  constructor(locale) {
    super(locale);

    /**
     * Tracks information about the DOM focus in the form.
     *
     * @readonly
     * @member {module:utils/focustracker~FocusTracker}
     */
    this.focusTracker = new FocusTracker();

    /**
     * An instance of the {@link module:utils/keystrokehandler~KeystrokeHandler}.
     *
     * @readonly
     * @member {module:utils/keystrokehandler~KeystrokeHandler}
     */
    this.keystrokes = new KeystrokeHandler();

    /**
     * A toggle for marking the image as decorative.
     *
     * @member {module:ui/button/switchbuttonview~SwitchButtonView} #decorativeToggle
     */
    this.decorativeToggle = this._decorativeToggleView();

    /**
     * An input with a label.
     *
     * @member {module:ui/labeledfield/labeledfieldview~LabeledFieldView} #labeledInput
     */
    this.labeledInput = this._createLabeledInputView();

    /**
     * A button used to submit the form.
     *
     * @member {module:ui/button/buttonview~ButtonView} #saveButtonView
     */
    this.saveButtonView = this._createButton(
      Drupal.t('Save'),
      icons.check,
      'ck-button-save',
    );
    this.saveButtonView.type = 'submit';
    // Save button is disabled when image is not decorative and alt text is
    // empty.
    this.saveButtonView
      .bind('isEnabled')
      .to(
        this.decorativeToggle,
        'isOn',
        this.labeledInput,
        'isEmpty',
        (isDecorativeToggleOn, isLabeledInputEmpty) =>
          isDecorativeToggleOn || !isLabeledInputEmpty,
      );

    /**
     * A button used to cancel the form.
     *
     * @member {module:ui/button/buttonview~ButtonView} #cancelButtonView
     */
    this.cancelButtonView = this._createButton(
      Drupal.t('Cancel'),
      icons.cancel,
      'ck-button-cancel',
      'cancel',
    );

    /**
     * A collection of views which can be focused in the form.
     *
     * @member {module:ui/viewcollection~ViewCollection}
     *
     * @readonly
     * @protected
     */
    this._focusables = new ViewCollection();

    /**
     * Helps cycling over focusables in the form.
     *
     * @member {module:ui/focuscycler~FocusCycler}
     *
     * @readonly
     * @protected
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
        class: [
          'ck',
          'ck-text-alternative-form',
          'ck-text-alternative-form--with-decorative-toggle',
          'ck-responsive-form',
        ],

        // https://github.com/ckeditor/ckeditor5-image/issues/40
        tabindex: '-1',
      },

      children: [
        {
          tag: 'div',
          attributes: {
            class: ['ck', 'ck-text-alternative-form__decorative-toggle'],
          },
          children: [this.decorativeToggle],
        },
        this.labeledInput,
        this.saveButtonView,
        this.cancelButtonView,
      ],
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

    [
      this.decorativeToggle,
      this.labeledInput,
      this.saveButtonView,
      this.cancelButtonView,
    ].forEach((v) => {
      // Register the view as focusable.
      this._focusables.add(v);

      // Register the view in the focus tracker.
      this.focusTracker.add(v.element);
    });
  }

  /**
   * @inheritdoc
   */
  destroy() {
    super.destroy();

    this.focusTracker.destroy();
    this.keystrokes.destroy();
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
   * @returns {module:ui/button/buttonview~ButtonView}
   *   The button view instance.
   *
   * @private
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
   * @returns {module:ui/labeledfield/labeledfieldview~LabeledFieldView}
   *   Labeled field view instance.
   *
   * @private
   */
  _createLabeledInputView() {
    const labeledInput = new LabeledFieldView(
      this.locale,
      createLabeledInputText,
    );

    labeledInput
      .bind('class')
      .to(this.decorativeToggle, 'isOn', (value) => (value ? 'ck-hidden' : ''));
    labeledInput.label = Drupal.t('Alternative text');

    return labeledInput;
  }

  /**
   * Creates a decorative image toggle view.
   *
   * @return {module:ui/button/switchbuttonview~SwitchButtonView}
   *   Decorative image toggle view instance.
   *
   * @private
   */
  _decorativeToggleView() {
    const decorativeToggle = new SwitchButtonView(this.locale);
    decorativeToggle.set({
      withText: true,
      label: Drupal.t('Decorative image'),
    });
    decorativeToggle.on('execute', () => {
      decorativeToggle.set('isOn', !decorativeToggle.isOn);
    });

    return decorativeToggle;
  }
}

/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore focusables switchbuttonview */

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
  Template,
} from 'ckeditor5/src/ui';
import { FocusTracker, KeystrokeHandler } from 'ckeditor5/src/utils';
import { IconCheck, IconCancel } from '@ckeditor/ckeditor5-icons';

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
     * A toggle for marking the image as decorative.
     *
     * @member {module:ui/button/switchbuttonview~SwitchButtonView} #decorativeToggle
     */
    this.decorativeToggle = this._decorativeToggleView();

    /**
     * An input with a label.
     */
    this.labeledInput = this._createLabeledInputView();

    /**
     * A button used to submit the form.
     */
    this.saveButtonView = this._createButton(
      Drupal.t('Save'),
      IconCheck,
      'ck-button-save',
    );
    this.saveButtonView.type = 'submit';

    /**
     * A button used to cancel the form.
     */
    this.cancelButtonView = this._createButton(
      Drupal.t('Cancel'),
      IconCancel,
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
        class: ['ck', 'ck-media-alternative-text-form', 'ck-vertical-form'],
        tabindex: '-1',
      },

      children: [
        {
          tag: 'div',
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

    labeledInput
      .bind('class')
      .to(this.decorativeToggle, 'isOn', (value) => (value ? 'ck-hidden' : ''));
    labeledInput.label = Drupal.t('Alternative text override');
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
      if (decorativeToggle.isOn) {
        // Clear value when decorative alt is turned off.
        this.labeledInput.fieldView.element.value = '';
      }
      decorativeToggle.set('isOn', !decorativeToggle.isOn);
    });
    return decorativeToggle;
  }
}

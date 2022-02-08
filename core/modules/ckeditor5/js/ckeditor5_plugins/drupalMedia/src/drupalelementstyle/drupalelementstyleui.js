/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalelementstyleediting splitbutton imagestyle componentfactory */
import { Plugin } from 'ckeditor5/src/core';
import utils from '@ckeditor/ckeditor5-image/src/imagestyle/utils';
import {
  addToolbarToDropdown,
  ButtonView,
  createDropdown,
  SplitButtonView,
} from 'ckeditor5/src/ui';
import DrupalElementStyleEditing from './drupalelementstyleediting';

import { isObject } from '../utils';

/**
 * @module drupalMedia/drupalelementstyle/drupalelementstyleui
 */

/**
 * Returns the first argument it receives.
 *
 * @param {*} value
 *   Any value to be returned by this function.
 * @return {*}
 *   Any value passed as the first argument.
 */
const identity = (value) => {
  return value;
};

/**
 * Gets the dropdown title.
 *
 * @param {string} dropdownTitle
 *   The dropdown title.
 * @param {string} buttonTitle
 *   The button title.
 * @return {string}
 *   The generated dropdown title.
 */
const getDropdownButtonTitle = (dropdownTitle, buttonTitle) => {
  return (dropdownTitle ? `${dropdownTitle}: ` : '') + buttonTitle;
};

/**
 * Gets the UI Component name.
 *
 * This is used for getting unique component names for registering the UI
 * components in the component factory.
 *
 * @param {string} name
 *   The name of the component.
 * @return {string}
 *   The UI component name.
 *
 * @see module:ui/componentfactory~ComponentFactory
 */
function getUIComponentName(name) {
  return `drupalElementStyle:${name}`;
}

/**
 * The Drupal Element Style UI plugin.
 *
 * @extends module:core/plugin~Plugin
 *
 * @internal
 */
export default class DrupalElementStyleUi extends Plugin {
  /**
   * @inheritDoc
   */
  static get requires() {
    return [DrupalElementStyleEditing];
  }

  /**
   * @inheritDoc
   */
  init() {
    const plugins = this.editor.plugins;
    const toolbarConfig = this.editor.config.get('drupalMedia.toolbar') || [];

    const definedStyles = Object.values(
      plugins.get('DrupalElementStyleEditing').normalizedStyles,
    );

    definedStyles.forEach((styleConfig) => {
      this._createButton(styleConfig);
    });

    /**
     * A Drupal Element Style dropdown definition.
     *
     * @example
     *    config:
     *       drupalMedia:
     *         toolbar:
     *           - name: 'drupalMedia:alignment'
     *             title: 'Custom title for the dropdown'
     *             items:
     *               - 'drupalElementStyle:alignLeft'
     *               - 'drupalElementStyle:alignCenter'
     *               - 'drupalElementStyle:alignRight'
     *             defaultItem: 'drupalElementStyle:alignCenter'
     *
     * @typedef {Object} Drupal.CKEditor5~drupalElementStyleDropdownDefinition
     *
     * @prop {string} name
     *   The name of the dropdown used for identifying the dropdown.
     * @prop {string[]} items
     *   The items displayed in the dropdown. These must be styles defined in
     *   `drupalElementStyles.options`.
     * @prop {string} defaultItem
     *   The default item of the dropdown. This must be a style defined in
     *   `drupalElementStyles.options`.
     * @prop {string} [title]
     *   The title of the dropdown.
     *
     * @see module:drupalMedia/drupalelementstyle/drupalelementstyleediting:DrupalElementStyleEditing
     */
    const definedDropdowns = toolbarConfig.filter(isObject);

    definedDropdowns.forEach((dropdownConfig) => {
      this._createDropdown(dropdownConfig, definedStyles);
    });
  }

  /**
   * Creates a dropdown and stores it in the component factory.
   *
   * @param {Drupal.CKEditor5~drupalElementStyleDropdownDefinition} dropdownConfig
   *   The dropdown configuration.
   * @param {Drupal.CKEditor5~DrupalElementStyle[]} definedStyles
   *   A list of defined styles.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createDropdown(dropdownConfig, definedStyles) {
    const factory = this.editor.ui.componentFactory;

    factory.add(dropdownConfig.name, (locale) => {
      let defaultButton;

      const { defaultItem, items, title } = dropdownConfig;
      const buttonViews = items
        .filter((itemName) =>
          definedStyles.find(
            ({ name }) => getUIComponentName(name) === itemName,
          ),
        )
        .map((buttonName) => {
          const button = factory.create(buttonName);

          if (buttonName === defaultItem) {
            defaultButton = button;
          }

          return button;
        });

      if (items.length !== buttonViews.length) {
        utils.warnInvalidStyle({ dropdown: dropdownConfig });
      }

      const dropdownView = createDropdown(locale, SplitButtonView);
      const splitButtonView = dropdownView.buttonView;

      addToolbarToDropdown(dropdownView, buttonViews);

      splitButtonView.set({
        label: getDropdownButtonTitle(title, defaultButton.label),
        class: null,
        tooltip: true,
      });

      // If style is selected, show the currently selected style as the default
      // button of the split button.
      splitButtonView.bind('icon').toMany(buttonViews, 'isOn', (...areOn) => {
        const index = areOn.findIndex(identity);

        return index < 0 ? defaultButton.icon : buttonViews[index].icon;
      });

      // If style is selected, use the label of the selected style as the
      // default label of the split button.
      splitButtonView.bind('label').toMany(buttonViews, 'isOn', (...areOn) => {
        const index = areOn.findIndex(identity);

        return getDropdownButtonTitle(
          title,
          index < 0 ? defaultButton.label : buttonViews[index].label,
        );
      });

      // If one of the style is selected, render the split button as selected.
      splitButtonView
        .bind('isOn')
        .toMany(buttonViews, 'isOn', (...areOn) => areOn.some(identity));

      // If one of the styles is selected, add a CSS class to the split button
      // which modifies the styles to indicate that the splitbutton default
      // option is currently selected.
      splitButtonView
        .bind('class')
        .toMany(buttonViews, 'isOn', (...areOn) =>
          areOn.some(identity) ? 'ck-splitbutton_flatten' : null,
        );

      splitButtonView.on('execute', () => {
        if (!buttonViews.some(({ isOn }) => isOn)) {
          defaultButton.fire('execute');
        } else {
          dropdownView.isOpen = !dropdownView.isOpen;
        }
      });

      dropdownView
        .bind('isEnabled')
        .toMany(buttonViews, 'isEnabled', (...areEnabled) =>
          areEnabled.some(identity),
        );

      return dropdownView;
    });
  }

  /**
   * Creates a button and stores it in the editor component factory.
   *
   * @param {Drupal.CKEditor5~DrupalElementStyle} buttonConfig
   *   The button configuration.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createButton(buttonConfig) {
    const buttonName = buttonConfig.name;

    this.editor.ui.componentFactory.add(
      getUIComponentName(buttonName),
      (locale) => {
        const command = this.editor.commands.get('drupalElementStyle');
        const view = new ButtonView(locale);

        view.set({
          label: buttonConfig.title,
          icon: buttonConfig.icon,
          tooltip: true,
          isToggleable: true,
        });

        view.bind('isEnabled').to(command, 'isEnabled');
        view.bind('isOn').to(command, 'value', (value) => value === buttonName);
        view.on('execute', this._executeCommand.bind(this, buttonName));

        return view;
      },
    );
  }

  /**
   * Executes the Drupal Element Style command.
   *
   * @param {string} name
   *   The name of the style that should be applied.
   *
   * @see module:drupalMedia/drupalelementstyle/drupalelementstylecommand~DrupalElementStyleCommand
   *
   * @private
   */
  _executeCommand(name) {
    this.editor.execute('drupalElementStyle', { value: name });
    this.editor.editing.view.focus();
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'DrupalElementStyleUi';
  }
}

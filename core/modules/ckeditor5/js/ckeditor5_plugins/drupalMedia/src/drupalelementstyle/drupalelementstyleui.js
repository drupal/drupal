/* eslint-disable import/no-extraneous-dependencies */
/* cspell:ignore drupalelementstyleediting splitbutton imagestyle componentfactory buttonview */
import { Plugin } from 'ckeditor5/src/core';
import { Collection, toMap } from 'ckeditor5/src/utils';
import utils from '@ckeditor/ckeditor5-image/src/imagestyle/utils';
import {
  addToolbarToDropdown,
  addListToDropdown,
  ButtonView,
  createDropdown,
  DropdownButtonView,
  Model,
  SplitButtonView,
} from 'ckeditor5/src/ui';
import DrupalElementStyleEditing from './drupalelementstyleediting';
import { isObject } from '../utils';
import { getClosestElementWithElementStyleAttribute } from './utils';

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
 *   The name of the style
 * @param {string} group
 *   The group of the style.
 * @return {string}
 *   The UI component name.
 *
 * @see module:ui/componentfactory~ComponentFactory
 */
function getUIComponentName(name, group) {
  return `drupalElementStyle:${group}:${name}`;
}

/**
 * The Drupal Element Style UI plugin.
 *
 * @extends module:core/plugin~Plugin
 *
 * @private
 */
export default class DrupalElementStyleUi extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalElementStyleEditing];
  }

  /**
   * @inheritdoc
   */
  init() {
    const { plugins } = this.editor;
    const toolbarConfig = this.editor.config.get('drupalMedia.toolbar') || [];
    const definedStyles = plugins.get(
      'DrupalElementStyleEditing',
    ).normalizedStyles;

    Object.keys(definedStyles).forEach((group) => {
      definedStyles[group].forEach((style) => {
        this._createButton(style, group, definedStyles[group]);
      });
    });

    /**
     * A Drupal Element Style dropdown definition.
     * One dropdown definition can only contain items from one group.
     *
     * List dropdown display configuration.
     * @example
     *    config:
     *       drupalMedia:
     *        toolbar:
     *          - name: 'drupalMedia:viewMode'
     *            display: 'listDropdown'
     *            items:
     *              - 'drupalElementStyle:viewMode:default'
     *              - 'drupalElementStyle:viewMode:full'
     *              - 'drupalElementStyle:viewMode:media_library'
     *              - 'drupalElementStyle:viewMode:compact'
     *            defaultItem: 'drupalElementStyle:viewMode:default'
     *
     * Split button dropdown display configuration.
     * @example
     *    config:
     *       drupalMedia:
     *        toolbar:
     *          - name: 'drupalMedia:side'
     *            display: 'splitButton'
     *            items:
     *              - 'drupalElementStyle:side:right'
     *              - 'drupalElementStyle:side:left'
     *            defaultItem: 'drupalElementStyle:side:right'
     *
     * Toolbar buttons configuration (non-dropdown).
     * @example
     *    config:
     *       drupalMedia:
     *        toolbar:
     *          - 'drupalElementStyle:align:breakText'
     *          - 'drupalElementStyle:align:left'
     *          - 'drupalElementStyle:align:center'
     *          - 'drupalElementStyle:align:right'
     *
     * @typedef {Object} Drupal.CKEditor5~drupalElementStyleDropdownDefinition
     *
     * These properties are needed for a list or split button dropdown
     * configuration. Buttons directly on the toolbar without a dropdown can be
     * configured like in the align example above.
     * @prop {string} name
     *   The name of the dropdown used for identifying the dropdown, either as a
     *   list or icons.
     * @prop {string} display
     *   The type of the dropdown used. Available options are `listDropdown` and
     *   `splitButton`.
     * @prop {string[]} items
     *   The items displayed in the dropdown. These must be styles defined in
     *   `drupalElementStyles`.
     * @prop {string} defaultItem
     *   The default item of the dropdown. This must be a style defined in
     *   `drupalElementStyles`.
     * @prop {string} [title]
     *   The title of the dropdown.
     *
     * @see module:drupalMedia/drupalelementstyle/drupalelementstyleediting:DrupalElementStyleEditing
     */
    const definedDropdowns = toolbarConfig.filter(isObject).filter((obj) => {
      const items = [];
      if (!obj.display) {
        console.warn(
          'dropdown configuration must include a display key specifying either listDropdown or splitButton.',
        );
        return false;
      }
      if (!obj.items.includes(obj.defaultItem)) {
        console.warn(
          'defaultItem must be part of items in the dropdown configuration.',
        );
      }
      // eslint-disable-next-line no-restricted-syntax
      for (const item of obj.items) {
        const groupName = item.split(':')[1];
        items.push(groupName);
      }
      if (!items.every((i) => i === items[0])) {
        console.warn(
          'dropdown configuration should only contain buttons from one group.',
        );
        return false;
      }
      return true;
    });

    definedDropdowns.forEach((dropdownConfig) => {
      // Only create dropdowns if there are 2 or more items.
      if (dropdownConfig.items.length >= 2) {
        const groupName = dropdownConfig.name.split(':')[1];
        switch (dropdownConfig.display) {
          case 'splitButton':
            this._createDropdown(dropdownConfig, definedStyles[groupName]);
            break;
          case 'listDropdown':
            this._createListDropdown(dropdownConfig, definedStyles[groupName]);
            break;
          default:
            break;
        }
      }
    });
  }

  /**
   * Updates the visibility of options depending on the selection's media type.
   *
   * @param {Drupal.CKEditor5~DrupalElementStyleDefinition[]} definedStyles
   *   A list of defined styles of one group.
   * @param {Drupal.CKEditor5~DrupalElementStyleDefinition} style
   *   The style to check be checked against the media type's specific styles.
   * @param {module:ui/dropdown/utils~ListDropdownItemDefinition|module:ui/button/buttonview} option
   *   Dropdown item definition or ButtonView
   * @param {string} group
   *   Name of group of the defined styles.
   */
  updateOptionVisibility(definedStyles, style, option, group) {
    const { selection } = this.editor.model.document;
    // Convert DrupalElementStyle[] into an object.
    const definedStylesObject = {};
    definedStylesObject[group] = definedStyles;
    const modelElement = selection
      ? selection.getSelectedElement()
      : getClosestElementWithElementStyleAttribute(
          selection,
          this.editor.model.schema,
          definedStylesObject,
        );

    const filteredDefinedStyles = definedStyles.filter(function (item) {
      // eslint-disable-next-line no-restricted-syntax
      for (const [key, value] of toMap(item.modelAttributes)) {
        if (modelElement && modelElement.hasAttribute(key)) {
          return value.includes(modelElement.getAttribute(key));
        }
      }
      return true;
    });

    // List dropdown case.
    // Classes are set on the model of the dropdown item definition for list
    // dropdowns.
    if (option.hasOwnProperty('model')) {
      if (!filteredDefinedStyles.includes(style)) {
        // Hide the style option if it is not available for the media type that
        // the modelElement is.
        option.model.set({ class: 'ck-hidden' });
      } else {
        // Un-hide the style option here after changing selection to a media
        // type that should have the button visible.
        option.model.set({ class: '' });
      }
      // Split button case and non-dropdown toolbar button case.
      // Classes are set on the ButtonView.
    } else if (!filteredDefinedStyles.includes(style)) {
      option.set({ class: 'ck-hidden' });
    } else {
      option.set({ class: '' });
    }
  }

  /**
   * Creates a dropdown and stores it in the component factory.
   *
   * @param {Drupal.CKEditor5~drupalElementStyleDropdownDefinition} dropdownConfig
   *   The dropdown configuration.
   * @param {Drupal.CKEditor5~DrupalElementStyle[]} definedStyles
   *   A list of defined styles of one group.
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
        .filter((itemName) => {
          const groupName = itemName.split(':')[1];
          return definedStyles.find(
            ({ name }) => getUIComponentName(name, groupName) === itemName,
          );
        })
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
   * @param {string} group
   *   The name of the group (e.g. 'align', 'viewMode').
   * @param {Drupal.CKEditor5~DrupalElementStyleDefinition[]} definedStyles
   *   A list of defined styles of one group.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createButton(buttonConfig, group, definedStyles) {
    const buttonName = buttonConfig.name;

    this.editor.ui.componentFactory.add(
      getUIComponentName(buttonName, group),
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
        view.bind('isOn').to(command, 'value', (value) => {
          return value && value[group] === buttonName;
        });

        view.on('execute', this._executeCommand.bind(this, buttonName, group));

        this.listenTo(this.editor.ui, 'update', () => {
          this.updateOptionVisibility(definedStyles, buttonConfig, view, group);
        });

        return view;
      },
    );
  }

  /**
   * A helper function that parses the different dropdown options and returns
   * list item definitions ready for use in the dropdown.
   *
   * @param {Drupal.CKEditor5~DrupalElementStyleDefinition[]} definedStyles
   *   A list of defined styles of one group.
   * @param {module:drupalMedia/drupalelementstyle/drupalelementstylecommand} command
   *   The drupalElementStyle command.
   * @param {string} group
   *   The name of the group (e.g. 'align', 'viewMode').
   * @return {Iterable.<module:ui/dropdown/utils~ListDropdownItemDefinition>}
   *   Dropdown item definitions.
   *
   * @private
   */
  getDropdownListItemDefinitions(definedStyles, command, group) {
    const itemDefinitions = new Collection();
    definedStyles.forEach((style) => {
      const definition = {
        type: 'button',
        model: new Model({
          group,
          commandValue: style.name,
          label: style.title,
          withText: true,
          class: '',
        }),
      };
      itemDefinitions.add(definition);

      // Handles selecting another element's list dropdown button's visibility.
      // We need to listen to editor UI changes instead of selection because
      // visibility of the styles can be impacted by either selection or
      // changes to the model.
      this.listenTo(this.editor.ui, 'update', () => {
        this.updateOptionVisibility(definedStyles, style, definition, group);
      });
    });
    return itemDefinitions;
  }

  /**
   * A helper function that creates a list dropdown component.
   *
   * @param {Drupal.CKEditor5~drupalElementStyleDropdownDefinition} dropdownConfig
   *   The dropdown configuration.
   * @param {Drupal.CKEditor5~DrupalElementStyle[]} definedStyles
   *   A list of defined styles of one group.
   *
   * @private
   */
  _createListDropdown(dropdownConfig, definedStyles) {
    const factory = this.editor.ui.componentFactory;
    factory.add(dropdownConfig.name, (locale) => {
      let defaultButton;

      const { defaultItem, items, title, defaultText } = dropdownConfig;
      const group = dropdownConfig.name.split(':')[1];
      const buttonViews = items
        .filter((itemName) => {
          return definedStyles.find(
            ({ name }) => getUIComponentName(name, group) === itemName,
          );
        })
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

      const dropdownView = createDropdown(locale, DropdownButtonView);
      const dropdownButtonView = dropdownView.buttonView;

      dropdownButtonView.set({
        label: getDropdownButtonTitle(title, defaultButton.label),
        class: null,
        tooltip: defaultText,
        withText: true,
      });

      const command = this.editor.commands.get('drupalElementStyle');

      // If style is selected, use the label of the selected style as the
      // default label of the splitbutton.
      dropdownButtonView.bind('label').to(command, 'value', (commandValue) => {
        if (commandValue && commandValue[group]) {
          // eslint-disable-next-line no-restricted-syntax
          for (const style of definedStyles) {
            if (style.name === commandValue[group]) {
              return style.title;
            }
          }
        }
        return defaultText;
      });

      dropdownView.bind('isOn').to(command);
      dropdownView.bind('isEnabled').to(this);

      addListToDropdown(
        dropdownView,
        this.getDropdownListItemDefinitions(definedStyles, command, group),
      );
      // Execute command when an item from the dropdown is selected.
      this.listenTo(dropdownView, 'execute', (evt) => {
        this._executeCommand(evt.source.commandValue, evt.source.group);
      });
      return dropdownView;
    });
  }

  /**
   * Executes the Drupal Element Style command.
   *
   * @param {string} name
   *   The name of the style that should be applied.
   * @param {string} group
   *   The name of the group (e.g. 'align', 'viewMode').
   *
   * @see module:drupalMedia/drupalelementstyle/drupalelementstylecommand~DrupalElementStyleCommand
   *
   * @private
   */
  _executeCommand(name, group) {
    this.editor.execute('drupalElementStyle', {
      value: name,
      group,
    });
    this.editor.editing.view.focus();
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalElementStyleUi';
  }
}

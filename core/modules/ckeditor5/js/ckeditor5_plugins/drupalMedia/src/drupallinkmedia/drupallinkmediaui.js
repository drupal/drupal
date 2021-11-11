/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words linkui
import { Plugin } from 'ckeditor5/src/core';
import { LINK_KEYSTROKE } from '@ckeditor/ckeditor5-link/src/utils';
import { ButtonView } from 'ckeditor5/src/ui';
import linkIcon from '../../../../../icons/link.svg';

/**
 * The link media UI plugin.
 *
 * @internal
 */
export default class DrupalLinkMediaUI extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['LinkEditing', 'LinkUI', 'DrupalMediaEditing'];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalLinkMediaUi';
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const viewDocument = editor.editing.view.document;

    this.listenTo(
      viewDocument,
      'click',
      (evt, data) => {
        if (this._isSelectedLinkedMedia(editor.model.document.selection)) {
          // Prevent browser navigation when clicking a linked media.
          data.preventDefault();

          // Block the `LinkUI` plugin when a media was clicked. In such a case,
          // we'd like to display the media toolbar.
          evt.stop();
        }
      },
      { priority: 'high' },
    );
    this._createToolbarLinkMediaButton();
  }

  /**
   * Creates a `DrupalLinkMediaUI` button view.
   *
   * Clicking this button shows a {@link module:link/linkui~LinkUI#_balloon}
   * attached to the selection. When an media is already linked, the view shows
   * {@link module:link/linkui~LinkUI#actionsView} or
   * {@link module:link/linkui~LinkUI#formView} if it is not.
   */
  _createToolbarLinkMediaButton() {
    const { editor } = this;
    const { t } = editor;

    editor.ui.componentFactory.add('drupalLinkMedia', (locale) => {
      const button = new ButtonView(locale);
      const plugin = editor.plugins.get('LinkUI');
      const linkCommand = editor.commands.get('link');

      button.set({
        isEnabled: true,
        label: t('Link media'),
        icon: linkIcon,
        keystroke: LINK_KEYSTROKE,
        tooltip: true,
        isToggleable: true,
      });

      // Bind button to the command.
      button.bind('isEnabled').to(linkCommand, 'isEnabled');
      button.bind('isOn').to(linkCommand, 'value', (value) => !!value);

      // Show the actionsView or formView (both from LinkUI) on button click
      // depending on whether the media is already linked.
      this.listenTo(button, 'execute', () => {
        if (this._isSelectedLinkedMedia(editor.model.document.selection)) {
          plugin._addActionsView();
        } else {
          plugin._showUI(true);
        }
      });

      return button;
    });
  }

  /**
   * Returns true if a linked media is the only selected element in the model.
   *
   * @param {module:engine/model/selection~Selection} selection
   * @return {Boolean}
   */
  // eslint-disable-next-line class-methods-use-this
  _isSelectedLinkedMedia(selection) {
    const selectedModelElement = selection.getSelectedElement();
    return (
      !!selectedModelElement &&
      selectedModelElement.is('element', 'drupalMedia') &&
      selectedModelElement.hasAttribute('linkHref')
    );
  }
}

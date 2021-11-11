/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore medialibrary

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
// cspell:ignore medialibrary
import mediaIcon from '../theme/icons/medialibrary.svg';

/**
 * @internal
 */
export default class DrupalMediaUI extends Plugin {
  init() {
    const editor = this.editor;
    const options = this.editor.config.get('drupalMedia');
    if (!options) {
      return;
    }

    const { libraryURL, openDialog, dialogSettings = {} } = options;
    if (!libraryURL || typeof openDialog !== 'function') {
      return;
    }

    editor.ui.componentFactory.add('drupalMedia', (locale) => {
      const command = editor.commands.get('insertDrupalMedia');
      const buttonView = new ButtonView(locale);

      buttonView.set({
        label: editor.t('Insert Drupal Media'),
        icon: mediaIcon,
        tooltip: true,
      });

      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          libraryURL,
          ({ attributes }) => {
            editor.execute('insertDrupalMedia', attributes);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}

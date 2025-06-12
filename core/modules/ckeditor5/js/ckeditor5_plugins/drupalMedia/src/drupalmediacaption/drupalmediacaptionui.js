/* eslint-disable import/no-extraneous-dependencies */
import { Plugin } from 'ckeditor5/src/core';
import { IconCaption } from '@ckeditor/ckeditor5-icons';
import { ButtonView } from 'ckeditor5/src/ui';
import { getMediaCaptionFromModelSelection } from './utils';

/**
 * The caption media UI plugin.
 *
 * @private
 */
export default class DrupalMediaCaptionUI extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalMediaCaptionUI';
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const editingView = editor.editing.view;
    editor.ui.componentFactory.add('toggleDrupalMediaCaption', (locale) => {
      const button = new ButtonView(locale);
      const captionCommand = editor.commands.get('toggleMediaCaption');
      button.set({
        label: Drupal.t('Caption media'),
        icon: IconCaption,
        tooltip: true,
        isToggleable: true,
      });

      // Bind button isOn and isEnabled properties to the command.
      button.bind('isOn', 'isEnabled').to(captionCommand, 'value', 'isEnabled');

      button
        .bind('label')
        .to(captionCommand, 'value', (value) =>
          value
            ? Drupal.t('Toggle caption off')
            : Drupal.t('Toggle caption on'),
        );

      this.listenTo(button, 'execute', () => {
        editor.execute('toggleMediaCaption', { focusCaptionOnShow: true });

        // If a caption is present, highlight it and scroll to the selection.
        const modelCaptionElement = getMediaCaptionFromModelSelection(
          editor.model.document.selection,
        );
        if (modelCaptionElement) {
          const figcaptionElement =
            editor.editing.mapper.toViewElement(modelCaptionElement);

          editingView.scrollToTheSelection();

          editingView.change((writer) => {
            writer.addClass(
              'drupal-media__caption_highlighted',
              figcaptionElement,
            );
          });
        }
        editor.editing.view.focus();
      });

      return button;
    });
  }
}

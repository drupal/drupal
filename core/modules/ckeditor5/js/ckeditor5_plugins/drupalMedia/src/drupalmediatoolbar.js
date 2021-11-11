/* eslint-disable import/no-extraneous-dependencies */
import { Plugin } from 'ckeditor5/src/core';
import { WidgetToolbarRepository } from 'ckeditor5/src/widget';

import { getSelectedDrupalMediaWidget } from './utils';

/**
 * @internal
 */
export default class DrupalMediaToolbar extends Plugin {
  static get requires() {
    return [WidgetToolbarRepository];
  }

  static get pluginName() {
    return 'DrupalMediaToolbar';
  }

  afterInit() {
    const editor = this.editor;
    const { t } = editor;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

    widgetToolbarRepository.register('drupalMedia', {
      ariaLabel: t('Drupal Media toolbar'),
      items: editor.config.get('drupalMedia.toolbar') || [],
      // Get the selected image or an image containing the figcaption with the selection inside.
      getRelatedElement: (selection) => getSelectedDrupalMediaWidget(selection),
    });
  }
}

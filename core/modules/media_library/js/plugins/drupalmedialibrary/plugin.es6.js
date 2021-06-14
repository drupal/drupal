/**
 * @file
 * Drupal Media Library plugin.
 */

(function (Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('drupalmedialibrary', {
    requires: 'drupalmedia',
    icons: 'drupalmedialibrary',
    hidpi: true,
    beforeInit(editor) {
      editor.addCommand('drupalmedialibrary', {
        allowedContent: {
          'drupal-media': {
            attributes: {
              '!data-entity-type': true,
              '!data-entity-uuid': true,
              '!data-view-mode': true,
              '!data-align': true,
              '!data-caption': true,
              '!alt': true,
              '!title': true,
            },
            classes: {},
          },
        },
        // This does not use the object format used above, but a
        // CKEDITOR.style instance, because requiredContent does not support
        // the object format.
        // @see https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_filter_contentRule.html
        // eslint-disable-next-line new-cap
        requiredContent: new CKEDITOR.style({
          element: 'drupal-media',
          attributes: {
            'data-entity-type': '',
            'data-entity-uuid': '',
          },
        }),
        modes: { wysiwyg: 1 },
        // There is an edge case related to the undo functionality that will
        // be resolved in https://www.drupal.org/project/drupal/issues/3073294.
        canUndo: true,
        // eslint-disable-next-line no-shadow
        exec(editor) {
          const saveCallback = function (values) {
            editor.fire('saveSnapshot');
            const mediaElement = editor.document.createElement('drupal-media');
            // eslint-disable-next-line prefer-destructuring
            const attributes = values.attributes;
            Object.keys(attributes).forEach((key) => {
              mediaElement.setAttribute(key, attributes[key]);
            });
            editor.insertHtml(mediaElement.getOuterHtml());
            editor.fire('saveSnapshot');
          };

          // @see \Drupal\media_library\MediaLibraryUiBuilder::dialogOptions()
          Drupal.ckeditor.openDialog(
            editor,
            editor.config.DrupalMediaLibrary_url,
            {},
            saveCallback,
            editor.config.DrupalMediaLibrary_dialogOptions,
          );
        },
      });

      if (editor.ui.addButton) {
        editor.ui.addButton('DrupalMediaLibrary', {
          label: Drupal.t('Insert from Media Library'),
          command: 'drupalmedialibrary',
        });
      }
    },
  });
})(Drupal, CKEDITOR);

<?php

/**
 * @file
 * Post update functions for CKEditor.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\editor\Entity\Editor;

/**
 * Updates Text Editors using CKEditor 4 to omit settings for disabled plugins.
 */
function ckeditor_post_update_omit_settings_for_disabled_plugins(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'editor', function (Editor $editor): bool {
    // Only try to update editors using CKEditor 4.
    if ($editor->getEditor() !== 'ckeditor') {
      return FALSE;
    }

    $enabled_plugins = _ckeditor_get_enabled_plugins($editor);

    // Only update if the editor has plugin settings for disabled plugins.
    $needs_update = FALSE;
    $settings = $editor->getSettings();

    // Updates are not needed if plugin settings are not defined for the editor.
    if (!isset($settings['plugins'])) {
      return FALSE;
    }

    foreach (array_keys($settings['plugins']) as $plugin_id) {
      if (!in_array($plugin_id, $enabled_plugins, TRUE)) {
        $needs_update = TRUE;
      }
    }

    return $needs_update;
  });
}

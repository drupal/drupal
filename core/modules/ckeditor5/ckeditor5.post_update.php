<?php

/**
 * @file
 * Post update functions for CKEditor 5.
 */

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\editor\Entity\Editor;

/**
 * Implements hook_removed_post_updates().
 */
function ckeditor5_removed_post_updates() {
  return [
    'ckeditor5_post_update_alignment_buttons' => '10.0.0',
  ];
}

/**
 * The image toolbar item changed from `uploadImage` to `drupalInsertImage`.
 */
function ckeditor5_post_update_image_toolbar_item(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (Editor $editor) {
    // Only try to update editors using CKEditor 5.
    if ($editor->getEditor() !== 'ckeditor5') {
      return FALSE;
    }

    $needs_update = FALSE;
    // Only update if the editor is using the `uploadImage` toolbar item.
    $settings = $editor->getSettings();
    if (is_array($settings['toolbar']['items']) && in_array('uploadImage', $settings['toolbar']['items'], TRUE)) {
      // Replace `uploadImage` with `drupalInsertImage`.
      $settings['toolbar']['items'] = str_replace('uploadImage', 'drupalInsertImage', $settings['toolbar']['items']);
      // `<img data-entity-uuid data-entity-type>` are implicitly supported when
      // uploads are enabled as the attributes are necessary for upload
      // functionality. If uploads aren't enabled, these attributes must still
      // be supported to ensure existing content that may have them (despite
      // uploads being disabled) remains editable. In this use case, the
      // attributes are added to the `ckeditor5_sourceEditing` allowed tags.
      if (!$editor->getImageUploadSettings()['status']) {
        // Add `sourceEditing` toolbar item if it does not already exist.
        if (!in_array('sourceEditing', $settings['toolbar']['items'], TRUE)) {
          $settings['toolbar']['items'][] = '|';
          $settings['toolbar']['items'][] = 'sourceEditing';
          // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing::defaultConfiguration()
          $settings['plugins']['ckeditor5_sourceEditing'] = ['allowed_tags' => []];
        }
        // Update configuration.
        $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = HTMLRestrictions::fromString(implode(' ', $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']))
          ->merge(HTMLRestrictions::fromString('<img data-entity-uuid data-entity-type>'))
          ->toCKEditor5ElementsArray();
      }
      $needs_update = TRUE;
    }
    if ($needs_update) {
      $editor->setSettings($settings);
    }
    return $needs_update;
  };

  $config_entity_updater->update($sandbox, 'editor', $callback);
}

/**
 * Updates Text Editors using CKEditor 5 to sort plugin settings by plugin key.
 */
function ckeditor5_post_update_plugins_settings_export_order(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'editor', function (Editor $editor): bool {
    // Only try to update editors using CKEditor 5.
    if ($editor->getEditor() !== 'ckeditor5') {
      return FALSE;
    }

    $settings = $editor->getSettings();

    // Nothing to do if there are fewer than two plugins with settings.
    if (count($settings['plugins']) < 2) {
      return FALSE;
    }
    ksort($settings['plugins']);
    $editor->setSettings($settings);

    return TRUE;
  });
}

/**
 * Updates Text Editors using CKEditor 5 Code Block.
 */
function ckeditor5_post_update_code_block(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'editor', function (Editor $editor): bool {
    // Only try to update editors using CKEditor 5.
    if ($editor->getEditor() !== 'ckeditor5') {
      return FALSE;
    }
    $settings = $editor->getSettings();
    return in_array('codeBlock', $settings['toolbar']['items'], TRUE);
  });
}

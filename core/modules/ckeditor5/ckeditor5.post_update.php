<?php

/**
 * @file
 * Post update functions for CKEditor 5.
 */

// cspell:ignore multiblock
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\editor\Entity\Editor;

/**
 * Implements hook_removed_post_updates().
 */
function ckeditor5_removed_post_updates(): array {
  return [
    'ckeditor5_post_update_alignment_buttons' => '10.0.0',
    'ckeditor5_post_update_image_toolbar_item' => '11.0.0',
    'ckeditor5_post_update_plugins_settings_export_order' => '11.0.0',
    'ckeditor5_post_update_code_block' => '11.0.0',
    'ckeditor5_post_update_list_multiblock' => '11.0.0',
    'ckeditor5_post_update_list_start_reversed' => '11.0.0',
  ];
}

/**
 * No-op update that didn't update quite enough the first time.
 */
function ckeditor5_post_update_list_type(array &$sandbox = []): void {
  // This update is intentionally left blank.
}

/**
 * Updates Text Editors using CKEditor 5 to native List "type" functionality.
 */
function ckeditor5_post_update_list_type_again(array &$sandbox = []): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'editor', function (Editor $editor): bool {
    // Only try to update editors using CKEditor 5.
    if ($editor->getEditor() !== 'ckeditor5') {
      return FALSE;
    }
    $settings = $editor->getSettings();

    // @see Ckeditor5Hooks::editorPresave()
    return array_key_exists('ckeditor5_list', $settings['plugins']);
  });
}

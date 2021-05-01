<?php

/**
 * @file
 * Post update functions for File.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Add txt to allowed extensions for all fields that allow uploads of insecure files.
 */
function file_post_update_add_txt_if_allows_insecure_extensions(&$sandbox = NULL) {
  if (\Drupal::config('system.file')->get('allow_insecure_uploads')) {
    return t('The system is configured to allow insecure file uploads. No file field updates are necessary.');
  }

  $updater = function (FieldConfig $field) {
    // Determine if this field uses an item definition that extends FileItem.
    if (is_subclass_of($field->getItemDefinition()->getClass(), FileItem::class)) {
      $allowed_extensions_string = trim($field->getSetting('file_extensions'));
      $allowed_extensions = array_filter(explode(' ', $allowed_extensions_string));
      if (in_array('txt', $allowed_extensions, TRUE)) {
        // Since .txt is specifically allowed, there's nothing to do.
        return FALSE;
      }
      foreach ($allowed_extensions as $extension) {
        // Allow .txt if an insecure extension is allowed.
        if (preg_match(FileSystemInterface::INSECURE_EXTENSION_REGEX, 'test.' . $extension)) {
          $allowed_extensions_string .= ' txt';
          $field->setSetting('file_extensions', $allowed_extensions_string);
          return TRUE;
        }
      }
      return FALSE;
    }
  };
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'field_config', $updater);
}

<?php

/**
 * @file
 * Post update functions for Field module.
 */

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_removed_post_updates().
 */
function field_removed_post_updates(): array {
  return [
    'field_post_update_save_custom_storage_property' => '9.0.0',
    'field_post_update_entity_reference_handler_setting' => '9.0.0',
    'field_post_update_email_widget_size_setting' => '9.0.0',
    'field_post_update_remove_handler_submit_setting' => '9.0.0',
  ];
}

/**
 * Removes the purge_batch_size config.
 */
function field_post_update_clear_purge_batch_size(): ?TranslatableMarkup {
  $config = \Drupal::configFactory()->getEditable('field.settings');
  if (!$config->isNew()) {
    $purge_batch_size = $config->get('purge_batch_size');
    if ($purge_batch_size !== Settings::get('field_purge_batch_size', 50)) {
      return t("The configuration field.settings:field_purge_batch_size is deprecated and is set to non-default value @field_purge_batch_size. Support for setting this through configuration will be removed in Drupal 13.0.0 and must set in settings.php: \$settings['field_purge_batch_size'] = '@field_purge_batch_size';, see https://www.drupal.org/node/3494023.", [
        '@field_purge_batch_size' => $purge_batch_size,
      ]);
    }
    else {
      // Remove the configuration as purge_batch_size is the only key.
      $config->delete();
    }
  }
  return NULL;
}

<?php

/**
 * @file
 * Post update functions for Field module.
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * @addtogroup updates-8.0.0-beta
 * @{
 */

/**
 * Re-save all field storage config objects to add 'custom_storage' property.
 */
function field_post_update_save_custom_storage_property() {
  foreach (FieldStorageConfig::loadMultiple() as $field_storage_config) {
    $field_storage_config->save();
  }

  return t('All field storage configuration objects re-saved.');
}

/**
 * Fixes the 'handler' setting for entity reference fields.
 */
function field_post_update_entity_reference_handler_setting() {
  foreach (FieldConfig::loadMultiple() as $field_config) {
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $item_class = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';
    $class = $field_type_manager->getPluginClass($field_config->getType());
    if ($class === $item_class || is_subclass_of($class, $item_class)) {
      // field_field_config_presave() will fix the 'handler' setting on save.
      $field_config->save();
    }
  }

  return t('Selection handler for entity reference fields have been adjusted.');
}

/**
 * @} End of "addtogroup updates-8.0.0-beta".
 */

<?php

/**
 * @file
 * Post update functions for Field module.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
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

/**
 * @addtogroup updates-8.1.0
 * @{
 */

/**
 * Adds the 'size' setting for email widgets.
 */
function field_post_update_email_widget_size_setting() {
  foreach (EntityFormDisplay::loadMultiple() as $entity_form_display) {
    $changed = FALSE;
    foreach ($entity_form_display->getComponents() as $name => $options) {
      if (isset($options['type']) && $options['type'] === 'email_default') {
        $options['settings']['size'] = '60';
        $entity_form_display->setComponent($name, $options);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $entity_form_display->save();
    }
  }

  return t('The new size setting for email widgets has been added.');
}

/**
 * @} End of "addtogroup updates-8.1.0".
 */

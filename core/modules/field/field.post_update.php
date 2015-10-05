<?php

/**
 * @file
 * Post update functions for Field.
 */

use Drupal\field\Entity\FieldStorageConfig;

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
 * @} End of "addtogroup updates-8.0.0-beta".
 */

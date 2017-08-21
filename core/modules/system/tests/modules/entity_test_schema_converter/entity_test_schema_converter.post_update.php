<?php

/**
 * @file
 * Post update functions for entity_test_schema_converter.
 */

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchemaConverter;

/**
 * @addtogroup updates-8.4.x
 * @{
 */

/**
 * Update entity_test_update to be revisionable.
 */
function entity_test_schema_converter_post_update_make_revisionable(&$sandbox) {
  $revisionableSchemaConverter = new SqlContentEntityStorageSchemaConverter(
    'entity_test_update',
    \Drupal::entityTypeManager(),
    \Drupal::entityDefinitionUpdateManager(),
    \Drupal::service('entity.last_installed_schema.repository'),
    \Drupal::keyValue('entity.storage_schema.sql'),
    \Drupal::database()
  );

  $revisionableSchemaConverter->convertToRevisionable(
    $sandbox,
    [
      'test_single_property',
      'test_multiple_properties',
      'test_single_property_multiple_values',
      'test_multiple_properties_multiple_values',
      'test_entity_base_field_info',
    ]);
}

/**
 * @} End of "addtogroup updates-8.4.x".
 */

<?php
use Drupal\Core\Entity\EntityInterface;

/**
 * @file
 * Defines an entity type.
 */

/**
 * Implements hook_entity_type_build().
 */
function field_test_entity_type_build() {
  /** @var $entity_info \Drupal\Core\Entity\EntityTypeInterface[] */
  // If requested, clear the field cache while this is being called. See
  // Drupal\field\Tests\FieldInfoTest::testFieldInfoCache().
  if (\Drupal::state()->get('field_test.clear_info_cache_in_hook_entity_type_build')) {
    field_info_cache_clear();
  }
}

/**
 * Implements hook_entity_type_alter().
 */
function field_test_entity_type_alter(array &$entity_types) {
  /** @var $entity_types \Drupal\Core\Entity\EntityTypeInterface[] */
  foreach (field_test_entity_info_translatable() as $entity_type => $translatable) {
    $entity_types[$entity_type]->set('translatable', $translatable);
  }
}

/**
 * Helper function to enable entity translations.
 */
function field_test_entity_info_translatable($entity_type = NULL, $translatable = NULL) {
  $stored_value = &drupal_static(__FUNCTION__, array());
  if (isset($entity_type)) {
    $stored_value[$entity_type] = $translatable;
    entity_info_cache_clear();
  }
  return $stored_value;
}

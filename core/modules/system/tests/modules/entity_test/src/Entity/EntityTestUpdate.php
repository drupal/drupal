<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestUpdate.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the test entity class for testing definition updates.
 *
 * This entity type starts out non-revisionable by lacking a "revision_id" key,
 * but during an update test, can be made revisionable by adding that key.
 *
 * @ContentEntityType(
 *   id = "entity_test_update",
 *   label = @Translation("Test entity update"),
 *   handlers = {
 *     "storage_schema" = "Drupal\entity_test\EntityTestStorageSchema"
 *   },
 *   base_table = "entity_test_update",
 *   revision_table = "entity_test_update_revision",
 *   fieldable = TRUE,
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   }
 * )
 */
class EntityTestUpdate extends EntityTestRev {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += \Drupal::state()->get('entity_test_update.additional_base_field_definitions', array());
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);
    $fields += \Drupal::state()->get('entity_test_update.additional_bundle_field_definitions.' . $bundle, array());
    return $fields;
  }

}

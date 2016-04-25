<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test entity class for testing default values.
 *
 * @ContentEntityType(
 *   id = "entity_test_field_override",
 *   label = @Translation("Test entity field overrides"),
 *   base_table = "entity_test_field_override",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestFieldOverride extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['name']->setDescription('The default description.');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    if ($bundle == 'some_test_bundle') {
      $fields['name'] = clone $base_field_definitions['name'];
      $fields['name']->setDescription('Custom description.');
    }
    return $fields;
  }
}

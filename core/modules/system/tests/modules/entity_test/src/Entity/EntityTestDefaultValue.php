<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestDefaultValue.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity class for testing default values.
 *
 * @ContentEntityType(
 *   id = "entity_test_default_value",
 *   label = @Translation("Test entity for default values"),
 *   base_table = "entity_test_default_value",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestDefaultValue extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Some custom description'))
      ->setDefaultValueCallback('entity_test_field_default_value');

    return $fields;
  }

}

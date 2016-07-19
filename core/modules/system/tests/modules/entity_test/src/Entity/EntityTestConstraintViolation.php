<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the test entity class for testing entity constraint violations.
 *
 * @ContentEntityType(
 *   id = "entity_test_constraint_violation",
 *   label = @Translation("Test entity constraint violation"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     }
 *   },
 *   base_table = "entity_test_constraint_violation",
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   }
 * )
 */
class EntityTestConstraintViolation extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name']->setDisplayOptions('form', array(
      'type' => 'string',
      'weight' => 0,
    ));
    $fields['name']->addConstraint('FieldWidgetConstraint', array());

    return $fields;
  }

}

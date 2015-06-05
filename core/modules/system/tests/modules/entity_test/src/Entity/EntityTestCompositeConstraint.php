<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestCompositeConstraint.
 */

namespace Drupal\entity_test\Entity;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test class for testing composite constraints.
 *
 * @ContentEntityType(
 *   id = "entity_test_composite_constraint",
 *   label = @Translation("Test entity constraints with composite constraint"),
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   },
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     }
 *   },
 *   base_table = "entity_test_composite_constraint",
 *   persistent_cache = FALSE,
 *   constraints = {
 *     "EntityTestComposite" = {},
 *     "EntityTestEntityLevel" = {},
 *   }
 * )
 */
class EntityTestCompositeConstraint extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name']->setDisplayOptions('form', array(
      'type' => 'string',
      'weight' => 0,
    ));

    $fields['type']->setDisplayOptions('form', array(
      'type' => 'entity_reference_autocomplete',
      'weight' => 0,
    ));

    return $fields;
  }

}

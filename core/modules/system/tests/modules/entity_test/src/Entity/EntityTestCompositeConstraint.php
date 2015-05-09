<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestCompositeConstraint.
 */

namespace Drupal\entity_test\Entity;

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
 *   base_table = "entity_test_composite_constraint",
 *   persistent_cache = FALSE,
 *   constraints = {
 *     "EntityTestComposite" = {},
 *   }
 * )
 */
class EntityTestCompositeConstraint extends EntityTest {

}

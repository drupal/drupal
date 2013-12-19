<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestDefaultAccess.
 */

namespace Drupal\entity_test\Entity;

/**
 * Defines a test entity class with no access controller.
 *
 * @EntityType(
 *   id = "entity_test_default_access",
 *   label = @Translation("Test entity with default access"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\FieldableDatabaseStorageController"
 *   },
 *   base_table = "entity_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestDefaultAccess extends EntityTest {

}

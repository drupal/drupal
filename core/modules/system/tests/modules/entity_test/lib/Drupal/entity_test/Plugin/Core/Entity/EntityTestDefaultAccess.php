<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Core\Entity\EntityTestDefaultAccess.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a test entity class with no access controller.
 *
 * @EntityType(
 *   id = "entity_test_default_access",
 *   label = @Translation("Test entity with default access"),
 *   module = "entity_test",
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController"
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

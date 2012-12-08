<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Core\Entity\EntityTestDefaultAccess.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a test entity class with no access controller.
 *
 * @Plugin(
 *   id = "entity_test_default_access",
 *   label = @Translation("Test entity with default access"),
 *   module = "entity_test",
 *   controller_class = "Drupal\entity_test\EntityTestStorageController",
 *   base_table = "entity_test",
 *   entity_keys = {
 *     "id" = "id"
 *   }
 * )
 */
class EntityTestDefaultAccess extends EntityTest {

}

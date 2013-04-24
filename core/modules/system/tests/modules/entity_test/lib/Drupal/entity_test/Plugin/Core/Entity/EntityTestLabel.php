<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Core\Entity\EntityTestLabel.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "entity_test_label",
 *   label = @Translation("Entity Test label"),
 *   module = "entity_test",
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController"
 *   },
 *   base_table = "entity_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestLabel extends EntityTest {

}

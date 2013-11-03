<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestNoLabel.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "entity_test_no_label",
 *   label = @Translation("Entity Test without label"),
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController"
 *   },
 *   field_cache = FALSE,
 *   base_table = "entity_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestNoLabel extends EntityTest {

}

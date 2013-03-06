<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Core\Entity\EntityTestNoLabel.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @Plugin(
 *   id = "entity_test_no_label",
 *   label = @Translation("Entity Test without label"),
 *   module = "entity_test",
 *   controller_class = "Drupal\entity_test\EntityTestStorageController",
 *   field_cache = FALSE,
 *   base_table = "entity_test",
 *   entity_keys = {
 *     "id" = "ftid",
 *   }
 * )
 */
class EntityTestNoLabel extends EntityTest {

}

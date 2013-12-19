<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestNoLabel.
 */

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "entity_test_no_label",
 *   label = @Translation("Entity Test without label"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\FieldableDatabaseStorageController"
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

<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestLabelCallback.
 */

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "entity_test_label_callback",
 *   label = @Translation("Entity test label callback"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\FieldableDatabaseStorageController"
 *   },
 *   field_cache = FALSE,
 *   base_table = "entity_test",
 *   revision_table = "entity_test_revision",
 *   label_callback = "entity_test_label_callback",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestLabelCallback extends EntityTest {

}

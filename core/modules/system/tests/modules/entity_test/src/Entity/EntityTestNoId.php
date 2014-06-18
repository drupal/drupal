<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestNoId.
 */

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_no_id",
 *   label = @Translation("Entity Test without id"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\ContentEntityNullStorage",
 *   },
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "admin-form" = "entity_test.admin_entity_test_no_id"
 *   }
 * )
 */
class EntityTestNoId extends EntityTest {

}

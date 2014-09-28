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
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\ContentEntityNullStorage",
 *   },
 *   entity_keys = {
 *     "bundle" = "type",
 *   },
 *   field_ui_base_route = "entity.entity_test_no_id.admin_form",
 * )
 */
class EntityTestNoId extends EntityTest {

}

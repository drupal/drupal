<?php

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
 *   admin_permission = "administer entity_test content",
 *   field_ui_base_route = "entity.entity_test_no_id.admin_form",
 *   links = {
 *     "add-form" = "/entity_test_no_id/add",
 *   },
 * )
 */
class EntityTestNoId extends EntityTest {

}

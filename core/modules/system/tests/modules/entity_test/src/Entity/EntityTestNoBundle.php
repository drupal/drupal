<?php

namespace Drupal\entity_test\Entity;

/**
 * Test entity class with no bundle.
 *
 * @ContentEntityType(
 *   id = "entity_test_no_bundle",
 *   label = @Translation("Entity Test without bundle"),
 *   base_table = "entity_test_no_bundle",
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *   },
 *   admin_permission = "administer entity_test content",
 *   links = {
 *     "add-form" = "/entity_test_no_bundle/add",
 *   },
 * )
 */
class EntityTestNoBundle extends EntityTest {

}

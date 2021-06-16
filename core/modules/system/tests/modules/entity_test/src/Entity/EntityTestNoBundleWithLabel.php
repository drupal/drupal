<?php

namespace Drupal\entity_test\Entity;

/**
 * Test entity class with no bundle but with label.
 *
 * @ContentEntityType(
 *   id = "entity_test_no_bundle_with_label",
 *   label = @Translation("Entity Test without bundle but with label"),
 *   base_table = "entity_test_no_bundle_with_label",
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "revision" = "revision_id",
 *   },
 *   admin_permission = "administer entity_test content",
 *   links = {
 *     "add-form" = "/entity_test_no_bundle_with_label/add",
 *   },
 * )
 */
class EntityTestNoBundleWithLabel extends EntityTest {

}

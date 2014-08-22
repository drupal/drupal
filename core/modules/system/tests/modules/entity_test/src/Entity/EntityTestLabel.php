<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestLabel.
 */

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_label",
 *   label = @Translation("Entity Test label"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder"
 *   },
 *   base_table = "entity_test",
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestLabel extends EntityTest {

}

<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestViewBuilder.
 */

namespace Drupal\entity_test\Entity;

/**
 * Test entity class for testing a view builder.
 *
 * @ContentEntityType(
 *   id = "entity_test_view_builder",
 *   label = @Translation("Entity Test view builder"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilderOverriddenView",
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
class EntityTestViewBuilder extends EntityTest {

}

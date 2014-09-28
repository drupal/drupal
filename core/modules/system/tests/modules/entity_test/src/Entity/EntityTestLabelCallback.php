<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestLabelCallback.
 */

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_label_callback",
 *   label = @Translation("Entity test label callback"),
 *   persistent_cache = FALSE,
 *   base_table = "entity_test",
 *   label_callback = "entity_test_label_callback",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestLabelCallback extends EntityTest {

}

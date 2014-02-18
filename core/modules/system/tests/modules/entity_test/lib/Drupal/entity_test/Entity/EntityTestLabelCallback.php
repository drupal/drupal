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

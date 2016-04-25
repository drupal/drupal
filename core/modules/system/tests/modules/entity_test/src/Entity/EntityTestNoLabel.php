<?php

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_no_label",
 *   label = @Translation("Entity Test without label"),
 *   persistent_cache = FALSE,
 *   base_table = "entity_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestNoLabel extends EntityTest {

  /**
   * @{inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

}

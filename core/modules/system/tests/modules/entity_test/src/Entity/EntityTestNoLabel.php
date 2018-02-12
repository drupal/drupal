<?php

namespace Drupal\entity_test\Entity;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_no_label",
 *   label = @Translation("Entity Test without label"),
 *   internal = TRUE,
 *   persistent_cache = FALSE,
 *   base_table = "entity_test_no_label",
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 * )
 */
class EntityTestNoLabel extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

}

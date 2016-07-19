<?php

namespace Drupal\entity_test\Entity;

/**
 * Defines a test entity class with no access control handler.
 *
 * @ContentEntityType(
 *   id = "entity_test_default_access",
 *   label = @Translation("Test entity with default access"),
 *   base_table = "entity_test_default_access",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestDefaultAccess extends EntityTest {

}

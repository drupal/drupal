<?php

/**
 * @file
 * Contains Drupal\entity_cache_test_dependency\Entity\EntityCacheTest.
 */

namespace Drupal\entity_cache_test_dependency\Entity;

use Drupal\Core\Entity\Entity;

/**
 * Defines the EntityCacheTest class.
 *
 * @EntityType(
 *   id = "entity_cache_test",
 *   label = @Translation("Entity cache test"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\EntityDatabaseStorage",
 *   }
 * )
 */
class EntityCacheTest extends Entity {

}

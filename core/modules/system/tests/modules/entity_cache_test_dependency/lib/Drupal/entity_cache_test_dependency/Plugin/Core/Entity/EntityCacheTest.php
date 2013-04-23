<?php

/**
 * @file
 * Contains Drupal\entity_cache_test_dependency\Plugin\Core\Entity\EntityCacheTest.
 */

namespace Drupal\entity_cache_test_dependency\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the EntityCacheTest class.
 *
 * @EntityType(
 *   id = "entity_cache_test",
 *   label = @Translation("Entity cache test"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController",
 *   },
 *   module = "entity_cache_test_dependency"
 * )
 */
class EntityCacheTest extends Entity {

}

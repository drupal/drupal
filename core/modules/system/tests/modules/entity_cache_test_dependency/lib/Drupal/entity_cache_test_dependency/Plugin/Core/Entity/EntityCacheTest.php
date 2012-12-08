<?php

/**
 * @file
 * Contains Drupal\entity_cache_test_dependency\Plugin\Core\Entity\EntityCacheTest.
 */

namespace Drupal\entity_cache_test_dependency\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the EntityCacheTest class.
 *
 * @Plugin(
 *   id = "entity_cache_test",
 *   label = @Translation("Entity cache test"),
 *   module = "entity_cache_test_dependency"
 * )
 */
class EntityCacheTest extends Entity {

}

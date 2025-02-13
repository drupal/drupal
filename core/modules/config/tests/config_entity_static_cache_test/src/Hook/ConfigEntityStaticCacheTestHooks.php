<?php

declare(strict_types=1);

namespace Drupal\config_entity_static_cache_test\Hook;

use Drupal\Component\Utility\Random;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_entity_static_cache_test.
 */
class ConfigEntityStaticCacheTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_load() for 'static_cache_test_config_test'.
   */
  #[Hook('config_test_load')]
  public function configTestLoad($entities): void {
    static $random;
    if (!$random) {
      $random = new Random();
    }
    foreach ($entities as $entity) {
      // Add a random stamp for every load(), so that during tests, we can tell
      // if an entity was retrieved from cache (unchanged stamp) or reloaded.
      $entity->_loadStamp = $random->string(8, TRUE);
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['config_test']->set('static_cache', TRUE);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\entity_test\Cache\EntityTestViewGrantsCacheContext.
 */

namespace Drupal\entity_test\Cache;

use Drupal\Core\Cache\CacheContextInterface;

/**
 * Defines the entity_test view grants cache context service.
 *
 * @see \Drupal\node\Cache\NodeAccessViewGrantsCacheContext
 */
class EntityTestViewGrantsCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Entity test view grants");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Return a constant value, so we can fetch render cache both in actual
    // requests and test code itself.
    return '299792458';
  }

}

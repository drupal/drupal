<?php

/**
 * @file
 * Contains \Drupal\entity_test\Cache\EntityTestViewGrantsCacheContext.
 */

namespace Drupal\entity_test\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the entity_test view grants cache context service.
 *
 * Cache context ID: 'entity_test_view_grants'.
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

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

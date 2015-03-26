<?php

/**
 * @file
 * Contains \Drupal\entity_test\Cache\EntityTestViewViewGrantsCacheContext.
 */

namespace Drupal\entity_test\Cache;

use Drupal\Core\Cache\CacheContextInterface;

/**
 * Defines the entity_test view grants cache context service.
 *
 * @see \Drupal\node\Cache\NodeAccessViewGrantsCacheContext
 */
class EntityTestViewViewGrantsCacheContext implements CacheContextInterface {

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
    return hash('sha256', REQUEST_TIME);
  }

}

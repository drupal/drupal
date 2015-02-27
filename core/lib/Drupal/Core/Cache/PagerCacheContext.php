<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\PagerCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines a cache context for "per page in a pager" caching.
 */
class PagerCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Pager');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($pager_id) {
    return 'pager.' . $pager_id . '.' . pager_find_page($pager_id);
  }

}

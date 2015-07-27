<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\PagersCacheContext.
 */

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines a cache context for "per page in a pager" caching.
 *
 * Cache context ID: 'url.query_args.pagers' (to vary by all pagers).
 * Calculated cache context ID: 'url.query_args.pagers:%pager_id', e.g.
 * 'url.query_args.pagers:1' (to vary by the pager with ID 1).
 */
class PagersCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Pager');
  }

  /**
   * {@inheritdoc}
   *
   * @see pager_find_page()
   */
  public function getContext($pager_id = NULL) {
    // The value of the 'page' query argument contains the information that
    // controls *all* pagers.
    if ($pager_id === NULL) {
      return $this->requestStack->getCurrentRequest()->query->get('page', '');
    }

    return $pager_id . '.' . pager_find_page($pager_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($pager_id = NULL) {
    return new CacheableMetadata();
  }

}

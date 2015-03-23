<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\PagersCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines a cache context for "per page in a pager" caching.
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
      return 'pager' . $this->requestStack->getCurrentRequest()->query->get('page', '');
    }

    return 'pager.' . $pager_id . '.' . pager_find_page($pager_id);
  }

}

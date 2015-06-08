<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\cache\None.
 */

namespace Drupal\views\Plugin\views\cache;

/**
 * Caching plugin that provides no caching at all.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("No caching of Views data.")
 * )
 */
class None extends CachePluginBase {

  public function summaryTitle() {
    return $this->t('None');
  }


  /**
   * Overrides \Drupal\views\Plugin\views\cache\CachePluginBase::cacheGet().
   *
   * Replace the cache get logic so it does not return a cache item at all.
   */
  public function cacheGet($type) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Replace the cache set logic so it does not set a cache item at all.
   */
  public function cacheSet($type) {
  }

}

<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\cache\None.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Caching plugin that provides no caching at all.
 *
 * @ingroup views_cache_plugins
 *
 * @Plugin(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("No caching of Views data.")
 * )
 */
class None extends CachePluginBase {

  public function cacheStart() { /* do nothing */ }

  public function summaryTitle() {
    return t('None');
  }


  /**
   * Overrides \Drupal\views\Plugin\views\cache\CachePluginBase::cache_get().
   *
   * Replace the cache get logic so it does not return a cache item at all.
   */
  function cache_get($type) {
    return FALSE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\cache\CachePluginBase::cache_set().
   *
   * Replace the cache set logic so it does set a cache item at all.
   */
  function cache_set($type) {
  }

}

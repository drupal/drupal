<?php

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsCache;

/**
 * Caching plugin that provides no caching at all.
 *
 * @ingroup views_cache_plugins
 */
#[ViewsCache(
  id: 'none',
  title: new TranslatableMarkup('None'),
  help: new TranslatableMarkup('No caching of Views data.'),
)]
class None extends CachePluginBase {

  /**
   * {@inheritdoc}
   */
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

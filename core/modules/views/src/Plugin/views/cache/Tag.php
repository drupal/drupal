<?php

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsCache;

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 */
#[ViewsCache(
  id: 'tag',
  title: new TranslatableMarkup('Tag based'),
  help: new TranslatableMarkup('Tag based caching of data. Caches will persist until any related cache tags are invalidated.'),
)]
class Tag extends CachePluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Tag');
  }

  /**
   * {@inheritdoc}
   */
  protected function cacheExpire($type) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCacheMaxAge() {
    return CacheBackendInterface::CACHE_PERMANENT;
  }

}

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
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3576855', E_USER_DEPRECATED);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCacheMaxAge() {
    return CacheBackendInterface::CACHE_PERMANENT;
  }

}

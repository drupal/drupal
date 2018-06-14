<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the SiteCacheContext service, for "per site" caching.
 *
 * Cache context ID: 'url.site'.
 *
 * A "site" is defined as the combination of URI scheme, domain name, port and
 * base path. It allows for varying between the *same* site being accessed via
 * different entry points. (Different sites in a multisite setup have separate
 * databases.) For example: http://example.com and http://www.example.com.
 *
 * @see \Symfony\Component\HttpFoundation\Request::getSchemeAndHttpHost()
 * @see \Symfony\Component\HttpFoundation\Request::getBaseUrl()
 */
class SiteCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Site');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();
    return $request->getSchemeAndHttpHost() . $request->getBaseUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

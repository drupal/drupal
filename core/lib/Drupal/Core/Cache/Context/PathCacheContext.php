<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the PathCacheContext service, for "per URL path" caching.
 *
 * Cache context ID: 'url.path'.
 *
 * (This allows for caching relative URLs.)
 *
 * @see \Symfony\Component\HttpFoundation\Request::getBasePath()
 * @see \Symfony\Component\HttpFoundation\Request::getPathInfo()
 */
class PathCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Path');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();
    return $request->getBasePath() . $request->getPathInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

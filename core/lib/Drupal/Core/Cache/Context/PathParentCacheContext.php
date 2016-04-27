<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines a cache context service for path parents.
 *
 * Cache context ID: 'url.path.parent'.
 *
 * This allows for caching based on the path, excluding everything after the
 * last forward slash.
 */
class PathParentCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Parent path');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();
    $path_elements = explode('/', trim($request->getPathInfo(), '/'));
    array_pop($path_elements);
    return implode('/', $path_elements);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

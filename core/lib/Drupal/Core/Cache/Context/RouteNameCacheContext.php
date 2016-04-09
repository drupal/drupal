<?php

namespace Drupal\Core\Cache\Context;

/**
 * Defines the RouteCacheContext service, for "per route name" caching.
 *
 * Cache context ID: 'route.name'.
 */
class RouteNameCacheContext extends RouteCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Route name');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->routeMatch->getRouteName();
  }

}

<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines the RouteCacheContext service, for "per route" caching.
 *
 * Cache context ID: 'route'.
 */
class RouteCacheContext implements CacheContextInterface {

  /**
   * Constructs a new RouteCacheContext class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Route');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->routeMatch->getRouteName() . hash('sha256', serialize($this->routeMatch->getRawParameters()->all()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

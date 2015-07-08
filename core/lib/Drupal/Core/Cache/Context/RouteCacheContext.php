<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\RouteCacheContext.
 */

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
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RouteCacheContext class.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
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

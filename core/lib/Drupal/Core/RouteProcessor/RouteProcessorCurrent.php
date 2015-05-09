<?php

/**
 * @file
 * Contains \Drupal\Core\RouteProcessor\RouteProcessorCurrent.
 */

namespace Drupal\Core\RouteProcessor;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a route processor to replace <current>.
 */
class RouteProcessorCurrent implements OutboundRouteProcessorInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RouteProcessorCurrent.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, CacheableMetadata $cacheable_metadata = NULL) {
    if ($route_name === '<current>') {
      if ($current_route = $this->routeMatch->getRouteObject()) {
        $route->setPath($current_route->getPath());
        $route->setRequirements($current_route->getRequirements());
        $route->setOptions($current_route->getOptions());
        $route->setDefaults($current_route->getDefaults());
        $parameters = array_merge($parameters, $this->routeMatch->getRawParameters()->all());
        if ($cacheable_metadata) {
          $cacheable_metadata->addCacheContexts(['route']);
        }
      }
      else {
        // If we have no current route match available, point to the frontpage.
        $route->setPath('/');
      }
    }
  }

}

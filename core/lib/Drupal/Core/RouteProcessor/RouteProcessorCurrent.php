<?php

namespace Drupal\Core\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
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
  public function processOutbound($route_name, Route $route, array &$parameters, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($route_name === '<current>') {
      if ($current_route = $this->routeMatch->getRouteObject()) {
        $requirements = $current_route->getRequirements();
        // Setting _method and _schema is deprecated since 2.7. Using
        // setMethods() and setSchemes() are now the recommended ways.
        unset($requirements['_method']);
        unset($requirements['_schema']);
        $route->setRequirements($requirements);

        $route->setPath($current_route->getPath());
        $route->setSchemes($current_route->getSchemes());
        $route->setMethods($current_route->getMethods());
        $route->setOptions($current_route->getOptions());
        $route->setDefaults($current_route->getDefaults());
        $parameters = array_merge($parameters, $this->routeMatch->getRawParameters()->all());
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheContexts(['route']);
        }
      }
      else {
        // If we have no current route match available, point to the frontpage.
        $route->setPath('/');
      }
    }
  }

}

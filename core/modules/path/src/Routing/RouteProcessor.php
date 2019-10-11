<?php

namespace Drupal\path\Routing;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Route;

/**
 * Processes the backwards-compatibility layer for path alias routes.
 */
class RouteProcessor implements OutboundRouteProcessorInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a RouteProcessor object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    $redirected_route_names = [
      'path.admin_add' => 'entity.path_alias.add_form',
      'path.admin_edit' => 'entity.path_alias.edit_form',
      'path.delete' => 'entity.path_alias.delete_form',
      'path.admin_overview' => 'entity.path_alias.collection',
      'path.admin_overview_filter' => 'entity.path_alias.collection',
    ];

    if (in_array($route_name, array_keys($redirected_route_names), TRUE)) {
      @trigger_error("The '{$route_name}' route is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the '{$redirected_route_names[$route_name]}' route instead. See https://www.drupal.org/node/3013865", E_USER_DEPRECATED);
      static::overwriteRoute($route, $this->routeProvider->getRouteByName($redirected_route_names[$route_name]));
    }
  }

  /**
   * Overwrites one route's metadata with the other's.
   *
   * @param \Symfony\Component\Routing\Route $target_route
   *   The route whose metadata to overwrite.
   * @param \Symfony\Component\Routing\Route $source_route
   *   The route whose metadata to read from.
   *
   * @see \Symfony\Component\Routing\Route
   */
  protected static function overwriteRoute(Route $target_route, Route $source_route) {
    $target_route->setPath($source_route->getPath());
    $target_route->setDefaults($source_route->getDefaults());
    $target_route->setRequirements($source_route->getRequirements());
    $target_route->setOptions($source_route->getOptions());
    $target_route->setHost($source_route->getHost());
    $target_route->setSchemes($source_route->getSchemes());
    $target_route->setMethods($source_route->getMethods());
  }

}

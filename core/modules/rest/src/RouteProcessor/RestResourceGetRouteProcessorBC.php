<?php

namespace Drupal\rest\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Route;

/**
 * Processes the BC REST routes, to ensure old route names continue to work.
 */
class RestResourceGetRouteProcessorBC implements OutboundRouteProcessorInterface {

  /**
   * The available serialization formats.
   *
   * @var string[]
   */
  protected $serializerFormats = [];

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a RestResourceGetRouteProcessorBC object.
   *
   * @param string[] $serializer_formats
   *   The available serialization formats.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(array $serializer_formats, RouteProviderInterface $route_provider) {
    $this->serializerFormats = $serializer_formats;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    $route_name_parts = explode('.', $route_name);
    // BC: the REST module originally created per-format GET routes, instead
    // of a single route. To minimize the surface of this BC layer, this uses
    // route definitions that are as empty as possible, plus an outbound route
    // processor.
    // @see \Drupal\rest\Plugin\ResourceBase::routes()
    if ($route_name_parts[0] === 'rest' && $route_name_parts[count($route_name_parts) - 2] === 'GET' && in_array($route_name_parts[count($route_name_parts) - 1], $this->serializerFormats, TRUE)) {
      array_pop($route_name_parts);
      $redirected_route_name = implode('.', $route_name_parts);
      @trigger_error(sprintf("The '%s' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the '%s' route instead.", $route_name, $redirected_route_name), E_USER_DEPRECATED);
      static::overwriteRoute($route, $this->routeProvider->getRouteByName($redirected_route_name));
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

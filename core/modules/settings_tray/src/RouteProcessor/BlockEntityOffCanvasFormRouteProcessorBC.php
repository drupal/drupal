<?php

namespace Drupal\settings_tray\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Route;

/**
 * Processes the Block entity off-canvas form BC route.
 *
 * @internal
 */
class BlockEntityOffCanvasFormRouteProcessorBC implements OutboundRouteProcessorInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a BlockEntityOffCanvasFormRouteProcessorBC object.
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
    if ($route_name === 'entity.block.off_canvas_form') {
      $redirected_route_name = 'entity.block.settings_tray_form';
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

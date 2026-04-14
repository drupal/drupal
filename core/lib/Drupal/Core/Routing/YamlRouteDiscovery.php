<?php

declare(strict_types=1);

namespace Drupal\Core\Routing;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Reads routing.yml files provided by modules and creates route collections.
 */
class YamlRouteDiscovery extends StaticRouteDiscoveryBase {

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly ControllerResolverInterface $controllerResolver,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    // Runs before PHP Attribute discovery.
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectRoutes(): iterable {
    foreach ($this->getRouteDefinitions() as $routes) {
      $collection = new RouteCollection();
      // The top-level 'routes_callback' is a list of methods in controller
      // syntax, see \Drupal\Core\Controller\ControllerResolver. These methods
      // should return a set of \Symfony\Component\Routing\Route objects, either
      // in an associative array keyed by the route name, which will be iterated
      // over and added to the collection for this provider, or as a new
      // \Symfony\Component\Routing\RouteCollection object, which will be added
      // to the collection.
      if (isset($routes['route_callbacks'])) {
        foreach ($routes['route_callbacks'] as $route_callback) {
          $callback = $this->controllerResolver->getControllerFromDefinition($route_callback);
          if ($callback_routes = call_user_func($callback)) {
            // If a RouteCollection is returned, add the whole collection.
            if ($callback_routes instanceof RouteCollection) {
              yield $callback_routes;
            }
            // Otherwise, add each Route object individually.
            else {
              foreach ($callback_routes as $name => $callback_route) {
                $collection->add($name, $callback_route);
              }
            }
          }
        }
        unset($routes['route_callbacks']);
      }
      foreach ($routes as $name => $route_info) {
        if (isset($route_info['alias'])) {
          $alias = $collection->addAlias($name, $route_info['alias']);
          $deprecation = $route_info['deprecated'] ?? NULL;
          if (isset($deprecation)) {
            $alias->setDeprecated(
              $deprecation['package'],
              $deprecation['version'],
              $deprecation['message'] ?? ''
            );
          }
          continue;
        }
        $route_info += $this->resetGlobals();

        $route = $this->createRoute($route_info['path'], $route_info['defaults'], $route_info['requirements'], $route_info['options'], $route_info['host'], $route_info['schemes'], $route_info['methods'], $route_info['condition'] ?? NULL);
        $collection->add($name, $route);
      }
      yield $collection;
    }
  }

  /**
   * Retrieves all defined routes from .routing.yml files.
   *
   * @return array
   *   The defined routes, keyed by provider.
   */
  protected function getRouteDefinitions() {
    // Always instantiate a new YamlDiscovery object so that we always search on
    // the up-to-date list of modules.
    $discovery = new YamlDiscovery('routing', $this->moduleHandler->getModuleDirectories());
    return $discovery->findAll();
  }

}

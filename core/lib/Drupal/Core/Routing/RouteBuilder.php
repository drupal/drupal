<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Access\CheckProviderInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\DestructableInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Managing class for rebuilding the router table.
 */
class RouteBuilder implements RouteBuilderInterface, DestructableInterface {

  /**
   * The dumper to which we should send collected routes.
   *
   * @var \Drupal\Core\Routing\MatcherDumperInterface
   */
  protected $dumper;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface $lock
   */
  protected $lock;

  /**
   * The event dispatcher to notify of routes.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The route collection during the rebuild.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * Flag that indicates if we are currently rebuilding the routes.
   *
   * @var bool
   */
  protected $building = FALSE;

  /**
   * Flag that indicates if we should rebuild at the end of the request.
   *
   * @var bool
   */
  protected $rebuildNeeded = FALSE;

  /**
   * The check provider.
   *
   * @var \Drupal\Core\Access\CheckProviderInterface
   */
  protected $checkProvider;

  /**
   * Constructs the RouteBuilder using the passed MatcherDumperInterface.
   *
   * @param \Drupal\Core\Routing\MatcherDumperInterface $dumper
   *   The matcher dumper used to store the route information.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher to notify of routes.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Access\CheckProviderInterface $check_provider
   *   The check provider.
   */
  public function __construct(MatcherDumperInterface $dumper, LockBackendInterface $lock, EventDispatcherInterface $dispatcher, ModuleHandlerInterface $module_handler, ControllerResolverInterface $controller_resolver, CheckProviderInterface $check_provider) {
    $this->dumper = $dumper;
    $this->lock = $lock;
    $this->dispatcher = $dispatcher;
    $this->moduleHandler = $module_handler;
    $this->controllerResolver = $controller_resolver;
    $this->checkProvider = $check_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildNeeded() {
    $this->rebuildNeeded = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    if ($this->building) {
      throw new \RuntimeException('Recursive router rebuild detected.');
    }

    if (!$this->lock->acquire('router_rebuild')) {
      // Wait for another request that is already doing this work.
      // We choose to block here since otherwise the routes might not be
      // available, resulting in a 404.
      $this->lock->wait('router_rebuild');
      return FALSE;
    }

    $this->building = TRUE;

    $collection = new RouteCollection();
    foreach ($this->getRouteDefinitions() as $routes) {
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
              $collection->addCollection($callback_routes);
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
        $route_info += [
          'defaults' => [],
          'requirements' => [],
          'options' => [],
          'host' => NULL,
          'schemes' => [],
          'methods' => [],
          'condition' => '',
        ];

        $route = new Route($route_info['path'], $route_info['defaults'], $route_info['requirements'], $route_info['options'], $route_info['host'], $route_info['schemes'], $route_info['methods'], $route_info['condition']);
        $collection->add($name, $route);
      }
    }

    // DYNAMIC is supposed to be used to add new routes based upon all the
    // static defined ones.
    $this->dispatcher->dispatch(RoutingEvents::DYNAMIC, new RouteBuildEvent($collection));

    // ALTER is the final step to alter all the existing routes. We cannot stop
    // people from adding new routes here, but we define two separate steps to
    // make it clear.
    $this->dispatcher->dispatch(RoutingEvents::ALTER, new RouteBuildEvent($collection));

    $this->checkProvider->setChecks($collection);

    $this->dumper->addRoutes($collection);
    $this->dumper->dump();

    $this->lock->release('router_rebuild');
    $this->dispatcher->dispatch(RoutingEvents::FINISHED, new Event());
    $this->building = FALSE;

    $this->rebuildNeeded = FALSE;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildIfNeeded() {
    if ($this->rebuildNeeded) {
      return $this->rebuild();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    // Rebuild routes only once at the end of the request lifecycle to not
    // trigger multiple rebuilds and also make the page more responsive for the
    // user.
    $this->rebuildIfNeeded();
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

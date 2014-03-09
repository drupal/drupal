<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuilder.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Managing class for rebuilding the router table.
 *
 * Because this class makes use of the modules system, it cannot currently
 * be unit tested.
 */
class RouteBuilder implements RouteBuilderInterface {

  /**
   * The dumper to which we should send collected routes.
   *
   * @var \Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface
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
   * The yaml discovery used to find all the .routing.yml files.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery
   */
  protected $yamlDiscovery;

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
   */
  public function __construct(MatcherDumperInterface $dumper, LockBackendInterface $lock, EventDispatcherInterface $dispatcher, ModuleHandlerInterface $module_handler, ControllerResolverInterface $controller_resolver, StateInterface $state = NULL) {
    $this->dumper = $dumper;
    $this->lock = $lock;
    $this->dispatcher = $dispatcher;
    $this->moduleHandler = $module_handler;
    $this->controllerResolver = $controller_resolver;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    if (!$this->lock->acquire('router_rebuild')) {
      // Wait for another request that is already doing this work.
      // We choose to block here since otherwise the routes might not be
      // available, resulting in a 404.
      $this->lock->wait('router_rebuild');
      return FALSE;
    }

    foreach ($this->getRouteDefinitions() as $provider => $routes) {
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
        $route_info += array(
          'defaults' => array(),
          'requirements' => array(),
          'options' => array(),
        );

        $route = new Route($route_info['path'], $route_info['defaults'], $route_info['requirements'], $route_info['options']);
        $collection->add($name, $route);
      }

      $this->dispatcher->dispatch(RoutingEvents::ALTER, new RouteBuildEvent($collection, $provider));
      $this->dumper->addRoutes($collection);
      $this->dumper->dump(array('provider' => $provider));
    }

    // Now allow modules to register additional, dynamic routes.
    // @todo Either remove this alter or the per-provider alter.
    $collection = new RouteCollection();
    $this->dispatcher->dispatch(RoutingEvents::ALTER, new RouteBuildEvent($collection, 'dynamic_routes'));
    $this->dumper->addRoutes($collection);
    $this->dumper->dump(array('provider' => 'dynamic_routes'));

    $this->state->delete(static::REBUILD_NEEDED);
    $this->lock->release('router_rebuild');
    $this->dispatcher->dispatch(RoutingEvents::FINISHED, new Event());
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildIfNeeded() {
    if ($this->state->get(static::REBUILD_NEEDED, FALSE)) {
      return $this->rebuild();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildNeeded() {
    $this->state->set(static::REBUILD_NEEDED, TRUE);
  }

  /**
   * Retrieves all defined routes from .routing.yml files.
   *
   * @return array
   *   The defined routes, keyed by provider.
   */
  protected function getRouteDefinitions() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('routing', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery->findAll();
  }

}

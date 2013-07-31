<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuilder.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\Discovery\YamlDiscovery;
use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Parser;
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
class RouteBuilder {

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
   * The extension handler for retieving the list of enabled modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construcs the RouteBuilder using the passed MatcherDumperInterface.
   *
   * @param \Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface $dumper
   *   The matcher dumper used to store the route information.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Symfony\Component\EventDispatcherEventDispatcherInterface
   *   The event dispatcher to notify of routes.
   */
  public function __construct(MatcherDumperInterface $dumper, LockBackendInterface $lock, EventDispatcherInterface $dispatcher, ModuleHandlerInterface $module_handler) {
    $this->dumper = $dumper;
    $this->lock = $lock;
    $this->dispatcher = $dispatcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Rebuilds the route info and dumps to dumper.
   */
  public function rebuild() {
    if (!$this->lock->acquire('router_rebuild')) {
      // Wait for another request that is already doing this work.
      // We choose to block here since otherwise the routes might not be
      // available, resulting in a 404.
      $this->lock->wait('router_rebuild');
      return;
    }

    $yaml_discovery = new YamlDiscovery('routing', $this->moduleHandler->getModuleDirectories());

    foreach ($yaml_discovery->findAll() as $module => $routes) {
      $collection = new RouteCollection();

      foreach ($routes as $name => $route_info) {
        $route_info += array(
          'defaults' => array(),
          'requirements' => array(),
          'options' => array(),
        );

        $route = new Route($route_info['pattern'], $route_info['defaults'], $route_info['requirements'], $route_info['options']);
        $collection->add($name, $route);
      }

      $this->dispatcher->dispatch(RoutingEvents::ALTER, new RouteBuildEvent($collection, $module));
      $this->dumper->addRoutes($collection);
      $this->dumper->dump(array('route_set' => $module));
    }

    // Now allow modules to register additional, dynamic routes.
    $collection = new RouteCollection();
    $this->dispatcher->dispatch(RoutingEvents::DYNAMIC, new RouteBuildEvent($collection, 'dynamic_routes'));
    $this->dispatcher->dispatch(RoutingEvents::ALTER, new RouteBuildEvent($collection, 'dynamic_routes'));
    $this->dumper->addRoutes($collection);
    $this->dumper->dump(array('route_set' => 'dynamic_routes'));

    $this->lock->release('router_rebuild');
  }

}

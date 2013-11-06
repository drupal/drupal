<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuilder.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\Discovery\YamlDiscovery;
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
   * Construcs the RouteBuilder using the passed MatcherDumperInterface.
   *
   * @param \Drupal\Core\Routing\MatcherDumperInterface $dumper
   *   The matcher dumper used to store the route information.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher to notify of routes.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(MatcherDumperInterface $dumper, LockBackendInterface $lock, EventDispatcherInterface $dispatcher, ModuleHandlerInterface $module_handler) {
    $this->dumper = $dumper;
    $this->lock = $lock;
    $this->dispatcher = $dispatcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Rebuilds the route info and dumps to dumper.
   *
   * @return bool
   *   Returns TRUE if the rebuild succeeds, FALSE otherwise.
   */
  public function rebuild() {
    if (!$this->lock->acquire('router_rebuild')) {
      // Wait for another request that is already doing this work.
      // We choose to block here since otherwise the routes might not be
      // available, resulting in a 404.
      $this->lock->wait('router_rebuild');
      return FALSE;
    }

    $yaml_discovery = $this->getYamlDiscovery();

    foreach ($yaml_discovery->findAll() as $module => $routes) {
      $collection = new RouteCollection();

      foreach ($routes as $name => $route_info) {
        $route_info += array(
          'defaults' => array(),
          'requirements' => array(),
          'options' => array(),
        );

        $route = new Route($route_info['path'], $route_info['defaults'], $route_info['requirements'], $route_info['options']);
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
    return TRUE;
  }

  /**
   * Returns the YAML discovery for getting all the .routing.yml files.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   The yaml discovery.
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('routing', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

}

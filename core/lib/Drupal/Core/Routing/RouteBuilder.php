<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuilder.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface;

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
   * Construcs the RouteBuilder using the passed MatcherDumperInterface.
   *
   * @param \Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface $dumper
   *   The matcher dumper used to store the route information.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(MatcherDumperInterface $dumper, LockBackendInterface $lock) {
    $this->dumper = $dumper;
    $this->lock = $lock;
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

    // We need to manually call each module so that we can know which module
    // a given item came from.

    foreach (module_implements('route_info') as $module) {
      $routes = call_user_func($module . '_route_info');
      drupal_alter('router_info', $routes, $module);
      $this->dumper->addRoutes($routes);
      $this->dumper->dump(array('route_set' => $module));
    }
    $this->lock->release('router_rebuild');
  }

}

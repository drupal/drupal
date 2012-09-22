<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RouteCompilerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface;

/**
 * Managing class for rebuilding the router table.
 *
 * Because this class makes use of the modules system, it cannot currently
 * be unit tested.
 */
class RouteBuilder {

  protected $dumper;

  /**
   * Construcs the RouteBuilder using the passed MatcherDumperInterface
   *
   * @param Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface $dumper
   *   The matcher dumper used to store the route information.
   */
  public function __construct(MatcherDumperInterface $dumper) {
    $this->dumper = $dumper;
  }

  /**
   * Rebuilds the route info and dumps to dumper.
   */
  public function rebuild() {
    // We need to manually call each module so that we can know which module
    // a given item came from.

    foreach (module_implements('route_info') as $module) {
      $routes = call_user_func($module . '_route_info');
      drupal_alter('router_info', $routes, $module);
      $this->dumper->addRoutes($routes);
      $this->dumper->dump(array('route_set' => $module));
    }
  }

}

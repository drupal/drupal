<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface as SymfonyMatcherDumperInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Extends the symfony matcher dumper interface with a addRoutes method.
 */
interface MatcherDumperInterface extends SymfonyMatcherDumperInterface {

  /**
   * Adds additional routes to be dumped.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   A collection of routes to add to this dumper.
   */
  public function addRoutes(RouteCollection $routes);

}

<?php

namespace Drupal\Core\Routing;

/**
 * Rebuilds the route information and dumps it.
 *
 * Rebuilding the route information is the process of gathering all routing data
 * from .routing.yml files, creating a
 * \Symfony\Component\Routing\RouteCollection object out of it, and dispatching
 * that object as a \Drupal\Core\Routing\RouteBuildEvent to all registered
 * listeners. After that, the \Symfony\Component\Routing\RouteCollection object
 * is used to dump the data. Examples of a dump include filling up the routing
 * table, auto-generating Apache mod_rewrite rules, or auto-generating a PHP
 * matcher class.
 *
 * @see \Drupal\Core\Routing\MatcherDumperInterface
 * @see \Drupal\Core\Routing\RouteProviderInterface
 *
 * @ingroup routing
 */
interface RouteBuilderInterface {

  /**
   * Rebuilds the route information and dumps it.
   *
   * @return bool
   *   Returns TRUE if the rebuild succeeds, FALSE otherwise.
   */
  public function rebuild();

  /**
   * Rebuilds the route information if necessary, and dumps it.
   *
   * @return bool
   *   Returns TRUE if the rebuild occurs, FALSE otherwise.
   */
  public function rebuildIfNeeded();

  /**
   * Sets the router to be rebuilt next time rebuildIfNeeded() is called.
   */
  public function setRebuildNeeded();

}

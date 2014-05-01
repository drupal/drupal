<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuilderInterface.
 */

namespace Drupal\Core\Routing;

interface RouteBuilderInterface {

  const REBUILD_NEEDED = 'router_rebuild_needed';

  /**
   * Rebuilds the route info and dumps to dumper.
   *
   * @return bool
   *   Returns TRUE if the rebuild succeeds, FALSE otherwise.
   */
  public function rebuild();

  /**
   * Returns the route collection during the rebuild.
   *
   * Don't use this function unless you really have to! Better pass along the
   * collection for yourself during the rebuild.
   *
   * Every use of this function is a design flaw of your code.
   *
   * @return \Symfony\Component\Routing\RouteCollection|FALSE
   */
  public function getCollectionDuringRebuild();

  /**
   * Rebuilds the route info and dumps to dumper if necessary.
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

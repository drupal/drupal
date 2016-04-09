<?php

namespace Drupal\Core\Routing;

interface RouteBuilderInterface {

  /**
   * Rebuilds the route info and dumps to dumper.
   *
   * @return bool
   *   Returns TRUE if the rebuild succeeds, FALSE otherwise.
   */
  public function rebuild();

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

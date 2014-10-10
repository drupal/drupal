<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\RouteBuilderIndicatorInterface.
 */

namespace Drupal\Core\Routing;

interface RouteBuilderIndicatorInterface {

  /**
   * The state key to use.
   */
  const REBUILD_NEEDED = 'router_rebuild_needed';

  /**
   * Sets the router to be rebuilt next time the kernel is terminated.
   *
   * @see \Drupal\Core\EventSubscriber\RouterRebuildSubscriber::onKernelTerminate()
   * @see \Drupal\Core\Routing\RouteBuilderInterface::rebuildIfNeeded()
   *
   */
  public function setRebuildNeeded();

  /**
   * Sets the router rebuild indicator to FALSE.
   */
  public function setRebuildDone();

  /**
   * Checks if the router needs to be rebuilt.
   *
   * @return bool
   *   TRUE if the router needs to be rebuilt, FALSE if not.
   */
  public function isRebuildNeeded();

}

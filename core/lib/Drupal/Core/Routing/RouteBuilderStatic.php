<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteBuilderStatic.
 */

namespace Drupal\Core\Routing;

/**
 * This builds a static version of the router.
 */
class RouteBuilderStatic implements RouteBuilderInterface {

  /**
   * Marks a rebuild as being necessary.
   *
   * @var bool
   */
  protected $rebuildNeeded = FALSE;

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    // @todo Add the route for the batch pages when that conversion happens,
    //   http://drupal.org/node/1987816.
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildIfNeeded(){
    if ($this->rebuildNeeded && $this->rebuild()) {
      $this->rebuildNeeded = FALSE;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildNeeded() {
    $this->rebuildNeeded = TRUE;
  }

  public function getCollectionDuringRebuild() {
    return FALSE;
  }

}

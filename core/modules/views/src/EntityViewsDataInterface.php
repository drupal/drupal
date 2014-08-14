<?php

/**
 * @file
 * Contains \Drupal\views\EntityViewsDataInterface.
 */

namespace Drupal\views;

/**
 * Provides an interface to integrate an entity type with views.
 */
interface EntityViewsDataInterface {

  /**
   * Returns views data for the entity type.
   *
   * @return array
   *   Views data in the format of hook_views_data().
   */
  public function getViewsData();

}

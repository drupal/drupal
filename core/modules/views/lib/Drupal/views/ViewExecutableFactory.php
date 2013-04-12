<?php

/**
 * @file
 * Contains \Drupal\views\ViewExecutableFactory.
 */

namespace Drupal\views;

use Drupal\views\ViewStorageInterface;

/**
 * Defines the cache backend factory.
 */
class ViewExecutableFactory {

  /**
   * Instantiates a ViewExecutable class.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   A view entity instance.
   *
   * @return \Drupal\views\ViewExecutable
   *   A ViewExecutable instance.
   */
  public static function get(ViewStorageInterface $view) {
    return new ViewExecutable($view);
  }

}

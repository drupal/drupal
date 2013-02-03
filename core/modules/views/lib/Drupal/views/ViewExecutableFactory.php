<?php

/**
 * @file
 * Contains \Drupal\views\ViewExecutableFactory.
 */

namespace Drupal\views;

use Drupal\views\Plugin\Core\Entity\View;

/**
 * Defines the cache backend factory.
 */
class ViewExecutableFactory {

  /**
   * Instantiates a ViewExecutable class.
   *
   * @param \Drupal\views\Plugin\Core\Entity\View $view
   *   A view entity instance.
   *
   * @return \Drupal\views\ViewExecutable
   *   A ViewExecutable instance.
   */
  public static function get(View $view) {
    return new ViewExecutable($view);
  }

}

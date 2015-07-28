<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\DisplayMenuInterface.
 */

namespace Drupal\views\Plugin\views\display;

/**
 * Defines an interface for displays that provide menu links.
 */
interface DisplayMenuInterface {

  /**
   * Gets menu links, if this display provides some.
   *
   * @return array
   *   The menu links registers for this display.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsMenuLink
   */
  public function getMenuLinks();

}

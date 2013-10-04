<?php

/**
 * @file
 * Contains \Drupal\menu_test\Controller\MenuTestController.
 */

namespace Drupal\menu_test\Controller;

/**
 * Controller routines for menu_test routes.
 */
class MenuTestController {

  /**
   * @todo Remove menu_test_callback().
   */
  public function menuTestCallback() {
    return menu_test_callback();
  }

  /**
   * @todo Remove menu_test_custom_403_404_callback().
   */
  public function custom403404() {
    return menu_test_custom_403_404_callback();
  }

  /**
   * @todo Remove menu_test_menu_trail_callback().
   */
  public function menuTrail() {
    return menu_test_menu_trail_callback();
  }

  /**
   * @todo Remove menu_test_theme_page_callback().
   */
  public function themePage($inherited) {
    return menu_test_theme_page_callback($inherited);
  }

}

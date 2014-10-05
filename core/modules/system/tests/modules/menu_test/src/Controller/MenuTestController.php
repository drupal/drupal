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
   * A title callback method for test routes.
   *
   * @param array $_title_arguments
   *   Optional array from the route defaults.
   * @param string $_title
   *   Optional _title string from the route defaults.
   *
   * @return string
   *   The route title.
   */
  public function titleCallback(array $_title_arguments = array(), $_title = '') {
    $_title_arguments += array('case_number' => '2', 'title' => $_title);
    return t($_title_arguments['title']) . ' - Case ' . $_title_arguments['case_number'];
  }

  /**
   * @todo Remove menu_test_theme_page_callback().
   */
  public function themePage($inherited) {
    return menu_test_theme_page_callback($inherited);
  }

  /**
   * A title callback for XSS breadcrumb check.
   *
   * @return string
   */
  public function breadcrumbTitleCallback() {
    return '<script>alert(123);</script>';
  }

}

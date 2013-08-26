<?php

/**
 * @file
 * Contains \Drupal\test_page_test\Controller\Test.
 */

namespace Drupal\test_page_test\Controller;

/**
 * Defines a test controller for page titles.
 */
class Test {

  /**
   * Renders a page with a title.
   *
   * @return array
   *   A render array as expected by drupal_render()
   */
  public function renderTitle() {
    $build = array();
    $build['#markup'] = 'Hello Drupal';
    $build['#title'] = 'Foo';

    return $build;
  }

}

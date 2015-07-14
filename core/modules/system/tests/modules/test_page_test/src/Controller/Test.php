<?php

/**
 * @file
 * Contains \Drupal\test_page_test\Controller\Test.
 */

namespace Drupal\test_page_test\Controller;

use Drupal\Component\Utility\SafeMarkup;

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

  /**
   * Renders a page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function staticTitle() {
    $build = array();
    $build['#markup'] = 'Hello Drupal';

    return $build;
  }

  /**
   * Returns a 'dynamic' title for the '_title_callback' route option.
   *
   * @return string
   *   The page title.
   */
  public function dynamicTitle() {
    return 'Dynamic title';
  }

  /**
   * Defines a controller with a cached render array.
   *
   * @param bool $mark_safe
   *   Whether or not to mark the title as safe use SafeMarkup::checkPlain.
   *
   * @return array
   *   A render array
   */
  public function controllerWithCache($mark_safe = FALSE) {
    $build = [];
    $build['#title'] = '<span>Cached title</span>';
    if ($mark_safe) {
      $build['#title'] = SafeMarkup::checkPlain($build['#title']);
    }
    $build['#cache']['keys'] = ['test_controller', 'with_title', $mark_safe];

    return $build;
  }

  /**
   * Returns a generic page render array for title tests.
   *
   * @return array
   *   A render array as expected by drupal_render()
   */
  public function renderPage() {
    return array(
      '#markup' => 'Content',
    );
  }

}

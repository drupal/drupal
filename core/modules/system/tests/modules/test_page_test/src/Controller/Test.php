<?php

namespace Drupal\test_page_test\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
   * @return array
   *   A render array
   */
  public function controllerWithCache() {
    $build = [];
    $build['#title'] = '<span>Cached title</span>';
    $build['#cache']['keys'] = ['test_controller', 'with_title'];

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

  /**
   * Throws a HTTP exception.
   *
   * @param int $code
   *   The status code.
   */
  public function httpResponseException($code) {
    throw new HttpException($code);
  }

  public function error() {
    trigger_error('foo', E_USER_NOTICE);
    return [
      '#markup' => 'Content',
    ];
  }

  /**
   * Renders a page with encoded markup.
   *
   * @return array
   *   A render array as expected by drupal_render()
   */
  public function renderEncodedMarkup() {
    return ['#plain_text' => 'Bad html <script>alert(123);</script>'];
  }

}

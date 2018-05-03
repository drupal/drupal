<?php

namespace Drupal\test_page_test\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Defines a test controller for page titles.
 */
class Test {

  /**
   * Renders a page with a title.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function renderTitle() {
    $build = [];
    $build['#markup'] = 'Hello Drupal';
    $build['#title'] = 'Foo';

    return $build;
  }

  /**
   * Renders a page.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function staticTitle() {
    $build = [];
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
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function renderPage() {
    return [
      '#markup' => 'Content',
    ];
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
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function renderEncodedMarkup() {
    return ['#plain_text' => 'Bad html <script>alert(123);</script>'];
  }

  /**
   * Renders a page with pipe character in link test.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function renderPipeInLink() {
    return ['#markup' => '<a href="http://example.com">foo|bar|baz</a>'];
  }

  /**
   * Loads a page that does a redirect.
   *
   * Drupal uses Symfony's RedirectResponse for generating redirects. That class
   * uses a lower-case 'http-equiv="refresh"'.
   *
   * @see \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function metaRefresh() {
    return new RedirectResponse(Url::fromRoute('test_page_test.test_page', [], ['absolute' => TRUE])->toString(), 302);
  }

}

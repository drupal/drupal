<?php

declare(strict_types=1);

namespace Drupal\test_page_test\Controller;

use Drupal\Core\Render\Markup;
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
  public function httpResponseException(int $code) {
    throw new HttpException($code);
  }

  public function error() {
    trigger_error('foo', E_USER_NOTICE);
    return [
      '#markup' => 'Content',
    ];
  }

  public function escapedCharacters() {
    return [
      '#prefix' => '<div class="escaped">',
      '#plain_text' => 'Escaped: <"\'&>',
      '#suffix' => '</div>',
    ];
  }

  public function escapedScript() {
    return [
      '#prefix' => '<div class="escaped">',
      // We use #plain_text because #markup would be filtered and that is not
      // being tested here.
      '#plain_text' => "<script>alert('XSS');alert(\"XSS\");</script>",
      '#suffix' => '</div>',
    ];
  }

  public function unEscapedScript() {
    return [
      '#prefix' => '<div class="unescaped">',
      '#markup' => Markup::create("<script>alert('Marked safe');alert(\"Marked safe\");</script>"),
      '#suffix' => '</div>',
    ];
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

  /**
   * Returns a page while triggering deprecation notices.
   */
  public function deprecations() {
    // Create 2 identical deprecation messages. This should only trigger a
    // single response header.
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('Test deprecation message', E_USER_DEPRECATED);
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('Test deprecation message', E_USER_DEPRECATED);
    return [
      '#markup' => 'Content that triggers deprecation messages',
    ];
  }

}

<?php

namespace Drupal\render_attached_test\Controller;

/**
 * Controller for various permutations of #attached in the render array.
 */
class RenderAttachedTestController {

  /**
   * Test special header and status code rendering.
   *
   * @return array
   *   A render array using features of the 'http_header' directive.
   */
  public function teapotHeaderStatus() {
    $render = [];
    $render['#attached']['http_header'][] = ['Status', "418 I'm a teapot."];
    return $render;
  }

  /**
   * Test attached HTML head rendering.
   *
   * @return array
   *   A render array using the 'http_head' directive.
   */
  public function header() {
    $render = [];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-Replace', 'This value gets replaced'];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-Replace', 'Teapot replaced', TRUE];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-No-Replace', 'This value is not replaced'];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-No-Replace', 'This one is added', FALSE];
    $render['#attached']['http_header'][] = ['X-Test-Teapot', 'Teapot Mode Active'];
    return $render;
  }

  /**
   * Test attached HTML head rendering.
   *
   * @return array
   *   A render array using the 'html_head' directive.
   */
  public function head() {
    $head = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'test-attribute' => 'testvalue',
        ],
      ],
      'test_head_attribute',
    ];

    $render = [];
    $render['#attached']['html_head'][] = $head;
    return $render;
  }

  /**
   * Test attached feed rendering.
   *
   * @return array
   *   A render array using the 'feed' directive.
   */
  public function feed() {
    $render = [];
    $render['#attached']['feed'][] = ['test://url', 'Your RSS feed.'];
    return $render;
  }

  /**
   * Test HTTP header rendering for link.
   *
   * @return array
   *   A render array using the 'html_head_link' directive.
   */
  public function htmlHeaderLink() {
    $render = [];
    $render['#attached']['html_head_link'][] = [['href' => '/foo?bar=<baz>&baz=false', 'rel' => 'alternate'], TRUE];
    $render['#attached']['html_head_link'][] = [['href' => '/not-added-to-http-headers', 'rel' => 'alternate'], FALSE];
    $render['#attached']['html_head_link'][] = [['href' => '/foo/bar', 'hreflang' => 'nl', 'rel' => 'alternate'], TRUE];
    return $render;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\httpkernel_test\Controller\TestController.
 */

namespace Drupal\httpkernel_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * A test controller.
 */
class TestController {

  /**
   * Return an empty response.
   */
  public function get() {
    return new Response();
  }

  /**
   * Test special header and status code rendering.
   *
   * @return array
   *   A render array using features of the 'http_header' directive.
   */
  public function teapot() {
    $render = [];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-Replace', 'This value gets replaced'];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-Replace', 'Teapot replaced', TRUE];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-No-Replace', 'This value is not replaced'];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-No-Replace', 'This one is added', FALSE];
    $render['#attached']['http_header'][] = ['X-Test-Teapot', 'Teapot Mode Active'];
    $render['#attached']['http_header'][] = ['Status', "418 I'm a teapot."];
    return $render;
  }

}

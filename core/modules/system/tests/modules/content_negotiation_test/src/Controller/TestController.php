<?php

declare(strict_types=1);

namespace Drupal\content_negotiation_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test controller for content negotiation tests.
 */
class TestController {

  /**
   * Returns a json response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function simple() {
    return new JsonResponse(['some' => 'data']);
  }

  /**
   * Returns a simple render array.
   *
   * @return array
   */
  public function html() {
    return [
      '#markup' => 'here',
    ];
  }

  /**
   * Returns different responses depending on the request format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function format(Request $request) {
    switch ($request->getRequestFormat()) {
      case 'json':
        return new JsonResponse(['some' => 'data']);

      case 'xml':
        return new Response('<xml></xml>', Response::HTTP_OK, ['Content-Type' => 'application/xml']);

      default:
        return new Response($request->getRequestFormat());
    }
  }

  /**
   * Returns a render array depending on some passed in value.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return array
   *   The render array
   */
  public function variable($plugin_id) {
    return [
      '#markup' => $plugin_id,
    ];
  }

}

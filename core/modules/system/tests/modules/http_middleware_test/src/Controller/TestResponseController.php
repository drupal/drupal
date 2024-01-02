<?php

declare(strict_types=1);

namespace Drupal\http_middleware_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for http_middleware_test routes.
 */
final class TestResponseController {

  /**
   * Returns a test response.
   */
  public function testResponse(): Response {
    return new Response('<html><body><p>Mangoes</p></body></html>');
  }

}

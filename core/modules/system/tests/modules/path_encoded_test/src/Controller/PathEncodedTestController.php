<?php

declare(strict_types=1);

namespace Drupal\path_encoded_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for path_encoded_test routes.
 */
class PathEncodedTestController {

  /**
   * Returns an HTML simple response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function simple() {
    return new Response('<html><body>PathEncodedTestController works</body></html>');
  }

}

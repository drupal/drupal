<?php

declare(strict_types=1);

namespace Drupal\csrf_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Just a test controller for test routes.
 */
class TestController {

  /**
   * Just a test method for the test routes.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function testMethod() {
    return new Response('Sometimes it is hard to think of test content!');
  }

}

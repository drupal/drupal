<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\TestController.
 */

namespace Drupal\Tests\Core\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a test controller used by unit tests.
 */
class TestController {

  /**
   * Returns test content for unit tests.
   */
  public function content() {
    return new Response('');
  }

}

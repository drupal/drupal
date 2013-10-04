<?php

/**
 * @file
 * Contains \Drupal\update_test\Controller\UpdateTestController.
 */

namespace Drupal\update_test\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides different routes of the update_test module.
 */
class UpdateTestController {


  /**
   * Displays an Error 503 (Service unavailable) page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns the response with a special header.
   */
  public function updateError() {
    $response = new Response();
    $response->setStatusCode(503);
    $response->headers->set('Status', '503 Service unavailable');

    return $response;
  }

  /**
   * @todo Remove update_test_mock_page().
   */
  public function updateTest($project_name, $version) {
    return update_test_mock_page($project_name, $version);
  }

}

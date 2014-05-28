<?php
/**
 * @file
 * Contains \Drupal\url_alter_test\Controller\URLAlterTestController.
 */

namespace Drupal\url_alter_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for url_alter_test routes.
 */
class URLAlterTestController {

  /**
   * Prints Current and Request Path.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function foo() {
    return new Response('current_path=' . current_path() . ' request_path=' . request_path());
  }

}

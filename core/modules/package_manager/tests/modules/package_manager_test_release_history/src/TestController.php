<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_release_history;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for generating mock XML metadata for package manager testing.
 */
class TestController extends ControllerBase {

  /**
   * Page callback: Prints mock XML for the Update Status module.
   *
   * @todo This is a wholesale copy of
   *   \Drupal\update_test\Controller\UpdateTestController::updateTest() for
   *   testing package_manager. This was done in order to use a different
   *   directory of mock XML files. Remove this module in
   *   https://drupal.org/i/3274826.
   */
  public function metadata($project_name = 'drupal', $version = NULL): Response {
    $xml_map = $this->config('update_test.settings')->get('xml_map');
    if (isset($xml_map[$project_name])) {
      $file = $xml_map[$project_name];
    }
    elseif (isset($xml_map['#all'])) {
      $file = $xml_map['#all'];
    }
    else {
      // The test didn't specify, for example, the webroot has other modules and
      // themes installed but they're disabled by the version of the site
      // running the test. So, we default to a file we know won't exist, so at
      // least we'll get an empty xml response instead of a bunch of Drupal page
      // output.
      $file = '#broken#';
    }

    $headers = ['Content-Type' => 'text/xml; charset=utf-8'];
    if (!is_file($file)) {
      // Return an empty response.
      return new Response('', 200, $headers);
    }
    return new BinaryFileResponse($file, 200, $headers);
  }

}

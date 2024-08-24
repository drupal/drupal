<?php

declare(strict_types=1);

namespace Drupal\update_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Provides different routes of the update_test module.
 */
class UpdateTestController extends ControllerBase {

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
   * Page callback: Prints mock XML for the Update Manager module.
   *
   * The specific XML file to print depends on two things: the project we're
   * trying to fetch data for, and the desired "availability scenario" for that
   * project which we're trying to test. Before attempting to fetch this data (by
   * checking for updates on the available updates report), callers need to define
   * the 'update_test_xml_map' variable as an array, keyed by project name,
   * indicating which availability scenario to use for that project.
   *
   * @param string $project_name
   *   The project short name the update manager is trying to fetch data for (the
   *   fetch URLs are of the form: [base_url]/[project_name]/[core_version]).
   * @param string $version
   *   The version of Drupal core.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|Response
   *   A BinaryFileResponse object containing the content of the XML release file
   *   for the specified project if one is available; a Response object with no
   *   content otherwise.
   */
  public function updateTest($project_name, $version) {
    $xml_map = $this->config('update_test.settings')->get('xml_map');
    if (isset($xml_map[$project_name])) {
      $availability_scenario = $xml_map[$project_name];
    }
    elseif (isset($xml_map['#all'])) {
      $availability_scenario = $xml_map['#all'];
    }
    else {
      // The test didn't specify a project nor '#all' (for all extensions on the
      // system). So, we default to a file we know won't exist, so at least
      // we'll get an empty xml response instead of a bunch of Drupal page
      // output.
      $availability_scenario = '#broken#';
    }

    $file = __DIR__ . "/../../../../fixtures/release-history/$project_name.$availability_scenario.xml";
    $headers = ['Content-Type' => 'text/xml; charset=utf-8'];
    if (!is_file($file)) {
      // Return an empty response.
      return new Response('', 200, $headers);
    }
    return new BinaryFileResponse($file, 200, $headers);
  }

}

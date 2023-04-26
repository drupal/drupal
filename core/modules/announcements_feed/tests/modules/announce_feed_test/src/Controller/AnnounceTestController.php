<?php

namespace Drupal\announce_feed_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller to return JSON for security advisory tests.
 */
class AnnounceTestController {

  /**
   * Reads a JSON file and returns the contents as a Response.
   *
   * This method will replace the string '[CORE_VERSION]' with the current core
   * version to allow testing core version matches.
   *
   * @param string $json_name
   *   The name of the JSON file without the file extension.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   If a fixture file with the name $json_name + '.json' is found a
   *   JsonResponse will be returned using the contents of the file, otherwise a
   *   Response will be returned with a 404 status code.
   */
  public function setFeedConfig(string $json_name): JsonResponse|Response {
    $file = __DIR__ . "/../../../../announce_feed/$json_name.json";
    $headers = ['Content-Type' => 'application/json; charset=utf-8'];
    if (!is_file($file)) {
      // Return an empty response.
      return new Response('', 404, $headers);
    }
    return new JsonResponse(file_get_contents($file), 200, $headers, TRUE);
  }

}

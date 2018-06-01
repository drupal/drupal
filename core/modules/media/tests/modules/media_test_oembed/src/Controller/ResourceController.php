<?php

namespace Drupal\media_test_oembed\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test controller returning oEmbed resources from Media's test fixtures.
 */
class ResourceController {

  /**
   * Returns the contents of an oEmbed resource fixture.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON response.
   */
  public function get(Request $request) {
    $asset_url = $request->query->get('url');

    $resources = \Drupal::state()->get(static::class, []);

    $content = file_get_contents($resources[$asset_url]);
    $response = new Response($content);
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }

  /**
   * Maps an asset URL to a local fixture representing its oEmbed resource.
   *
   * @param string $asset_url
   *   The asset URL.
   * @param string $resource_path
   *   The path of the oEmbed resource representing the asset.
   */
  public static function setResourceUrl($asset_url, $resource_path) {
    $resources = \Drupal::state()->get(static::class, []);
    $resources[$asset_url] = $resource_path;
    \Drupal::state()->set(static::class, $resources);
  }

}

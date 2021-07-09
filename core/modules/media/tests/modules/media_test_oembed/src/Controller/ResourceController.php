<?php

namespace Drupal\media_test_oembed\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test controller returning oEmbed resources from Media's test fixtures.
 */
class ResourceController {

  /**
   * Creates an oEmbed resource response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The oEmbed resource response.
   */
  public function get(Request $request) {
    $asset_url = $request->query->get('url');

    $resources = \Drupal::state()->get(static::class, []);

    if ($resources[$asset_url] === 404) {
      $response = new Response('Not Found', 404);
    }
    else {
      $content = file_get_contents($resources[$asset_url]);
      $response = new Response($content);
      $response->headers->set('Content-Type', 'application/json');
    }

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

  /**
   * Maps an asset URL to a 404 response.
   *
   * @param string $asset_url
   *   The asset URL.
   */
  public static function setResource404($asset_url) {
    $resources = \Drupal::state()->get(static::class, []);
    $resources[$asset_url] = 404;
    \Drupal::state()->set(static::class, $resources);
  }

}

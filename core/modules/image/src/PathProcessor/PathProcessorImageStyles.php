<?php

/**
 * @file
 * Contains \Drupal\image\PathProcessor\PathProcessorImageStyles.
 */

namespace Drupal\image\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite image styles URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 *
 * This processor handles two different cases:
 * - public image styles: In order to allow the webserver to serve these files
 *   directly, the route is registered under the same path as the image style so
 *   it took over the first generation. Therefore the path processor converts
 *   the file path to a query parameter.
 * - private image styles: In contrast to public image styles, private
 *   derivatives are already using system/files/styles. Similar to public image
 *   styles, it also converts the file path to a query parameter.
 */
class PathProcessorImageStyles implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $directory_path = file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath();
    if (strpos($path, $directory_path . '/styles/') === 0) {
      $path_prefix = $directory_path . '/styles/';
    }
    elseif (strpos($path, 'system/files/styles/') === 0) {
      $path_prefix = 'system/files/styles/';
    }
    else {
      return $path;
    }

    // Strip out path prefix.
    $rest = preg_replace('|^' . $path_prefix . '|', '', $path);

    // Get the image style, scheme and path.
    if (substr_count($rest, '/') >= 2) {
      list($image_style, $scheme, $file) = explode('/', $rest, 3);

      // Set the file as query parameter.
      $request->query->set('file', $file);

      return $path_prefix . $image_style . '/' . $scheme;
    }
    else {
      return $path;
    }
  }

}

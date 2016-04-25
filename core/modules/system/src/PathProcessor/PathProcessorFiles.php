<?php

namespace Drupal\system\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite file URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 */
class PathProcessorFiles implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/system/files/') === 0 && !$request->query->has('file')) {
      $file_path = preg_replace('|^\/system\/files\/|', '', $path);
      $request->query->set('file', $file_path);
      return '/system/files';
    }
    return $path;
  }

}

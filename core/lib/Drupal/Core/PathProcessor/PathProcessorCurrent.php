<?php

/**
 * @file
 * Contains \Drupal\Core\PathProcessor\PathProcessorCurrent.
 */

namespace Drupal\Core\PathProcessor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a path processor to replace <current>.
 */
class PathProcessorCurrent implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    if ($path == '%3Ccurrent%3E' && $request) {
      $request_uri = $request->getRequestUri();

      $current_base_path = $request->getBasePath() . '/';
      return substr($request_uri, strlen($current_base_path));
    }
    return $path;
  }

}


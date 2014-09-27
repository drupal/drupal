<?php

/**
 * @file
 * Contains \Drupal\Core\PathProcessor\PathProcessorNone.
 */

namespace Drupal\Core\PathProcessor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a path processor to replace <none>.
 */
class PathProcessorNone implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    if ($path == '%3Cnone%3E') {
      return '';
    }
    return $path;
  }

}

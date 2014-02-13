<?php

/**
 * @file
 * Contains Drupal\Core\PathProcessor\PathProcessorDecode.
 */

namespace Drupal\Core\PathProcessor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path by urldecoding it.
 *
 * Parameters in the URL sometimes represent code-meaningful strings. It is
 * therefore useful to always urldecode() those values so that individual
 * controllers need not concern themselves with it. This is Drupal-specific
 * logic and may not be familiar for developers used to other Symfony-family
 * projects.
 *
 * @todo Revisit whether or not this logic is appropriate for here or if
 *   controllers should be required to implement this logic themselves. If we
 *   decide to keep this code, remove this TODO.
 */
class PathProcessorDecode implements InboundPathProcessorInterface {

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processInbound().
   */
  public function processInbound($path, Request $request) {
    return urldecode($path);
  }

}

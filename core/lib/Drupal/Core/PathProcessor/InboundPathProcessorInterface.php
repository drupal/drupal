<?php

/**
 * @file
 * Contains Drupal\Core\PathProcessor\InboundPathProcessorInterface.
 */

namespace Drupal\Core\PathProcessor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for classes that process the inbound path.
 */
interface InboundPathProcessorInterface {

  /**
   * Processes the inbound path.
   *
   * @param string $path
   *   The path to process.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return string
   *   The processed path.
   */
  public function processInbound($path, Request $request);

}

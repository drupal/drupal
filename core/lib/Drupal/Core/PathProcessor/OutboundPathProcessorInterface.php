<?php

/**
 * @file
 * Contains Drupal\Core\PathProcessor\OutboundPathProcessorInterface.
 */

namespace Drupal\Core\PathProcessor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines an interface for classes that process the outbound path.
 */
interface OutboundPathProcessorInterface {

  /**
   * Processes the outbound path.
   *
   * @param string $path
   *   The path to process.
   *
   * @param array $options
   *   An array of options such as would be passed to the generator's
   *   generateFromPath() method.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return
   *   The processed path.
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL);

}

<?php

/**
 * @file
 * Contains \Drupal\Core\PathProcessor\OutboundPathProcessorInterface.
 */

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for classes that process the outbound path.
 */
interface OutboundPathProcessorInterface {

  /**
   * Processes the outbound path.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param array $options
   *   An array of options such as would be passed to the generator's
   *   generateFromRoute() method.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   (optional) Object to collect path processors' bubbleable metadata.
   *
   * @return string
   *   The processed path.
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL);

}

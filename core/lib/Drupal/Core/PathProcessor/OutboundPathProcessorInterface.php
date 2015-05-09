<?php

/**
 * @file
 * Contains Drupal\Core\PathProcessor\OutboundPathProcessorInterface.
 */

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for classes that process the outbound path.
 */
interface OutboundPathProcessorInterface {

  /**
   * Processes the outbound path.
   *
   * @param string $path
   *   The path to process.
   * @param array $options
   *   An array of options such as would be passed to the generator's
   *   generateFromPath() method.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   (optional) Object to collect path processors' cacheability.
   *
   * @return
   *   The processed path.
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, CacheableMetadata $cacheable_metadata = NULL);

}

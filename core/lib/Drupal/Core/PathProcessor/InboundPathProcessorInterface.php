<?php

namespace Drupal\Core\PathProcessor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for classes that process the inbound path.
 */
interface InboundPathProcessorInterface {

  /**
   * Processes the inbound path.
   *
   * Implementations may make changes to the request object passed in but should
   * avoid all other side effects. This method can be called to process requests
   * other than the current request.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the request to process. Note, if this
   *   method is being called via the path_processor_manager service and is not
   *   part of routing, the current request object must be cloned before being
   *   passed in.
   *
   * @return string
   *   The processed path.
   */
  public function processInbound($path, Request $request);

}

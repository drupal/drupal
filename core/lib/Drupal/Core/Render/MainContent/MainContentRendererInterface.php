<?php

namespace Drupal\Core\Render\MainContent;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The interface for "main content" (@code _controller @endcode) renderers.
 *
 * Classes implementing this interface are able to render the main content (as
 * received from controllers) into a response of  a certain format
 * (HTML, JSON …) and/or in a certain decorated manner (e.g. in the case of the
 * default HTML main content renderer: with a page display variant applied).
 */
interface MainContentRendererInterface  {

  /**
   * Renders the main content render array into a response.
   *
   * @param array $main_content
   *   The render array representing the main content.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object, for context.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match, for context.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The Response in the format that this implementation supports.
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match);

}

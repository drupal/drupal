<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface.
 */

namespace Drupal\views\Plugin\views\display;

/**
 * Defines a display which returns a Response object.
 *
 * This interface is meant to be used for display plugins, which do return some
 * other format requiring to return a response directly.
 */
interface ResponseDisplayPluginInterface extends DisplayPluginInterface {

  /**
   * Builds up a response with the rendered view as content.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   * @param array $args
   *   (optional) The arguments of the view.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The built response.
   */
  public static function buildResponse($view_id, $display_id, array $args = []);

}

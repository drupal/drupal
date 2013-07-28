<?php

/**
 * @file
 * Contains \Drupal\toolbar\Routing\ToolbarController.
 */

namespace Drupal\toolbar\Routing;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller for the toolbar module.
 */
class ToolbarController {

  /**
   * Returns the rendered subtree of each top-level toolbar link.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function subtreesJsonp() {
    _toolbar_initialize_page_cache();
    $subtrees = toolbar_get_rendered_subtrees();
    $response = new JsonResponse($subtrees);
    $response->setCallback('Drupal.toolbar.setSubtrees.resolve');
    return $response;
  }

}

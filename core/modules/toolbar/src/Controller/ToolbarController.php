<?php

/**
 * @file
 * Contains \Drupal\toolbar\Controller\ToolbarController.
 */

namespace Drupal\toolbar\Controller;

use Drupal\Core\Access\AccessInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller for the toolbar module.
 */
class ToolbarController extends ControllerBase {

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

  /**
   * Checks access for the subtree controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $langcode
   *   The langcode of the requested site, NULL if none given.
   *
   * @return string
   *   Returns AccessInterface::ALLOW when access was granted, otherwise
   *   AccessInterface::DENY.
   */
  public function checkSubTreeAccess(Request $request, $langcode) {
    $hash = $request->get('hash');
    return ($this->currentUser()->hasPermission('access toolbar') && ($hash == _toolbar_get_subtrees_hash($langcode))) ? AccessInterface::ALLOW : AccessInterface::DENY;
  }

}

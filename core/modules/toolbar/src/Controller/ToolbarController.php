<?php

/**
 * @file
 * Contains \Drupal\toolbar\Controller\ToolbarController.
 */

namespace Drupal\toolbar\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    $subtrees = toolbar_get_rendered_subtrees();
    $response = new JsonResponse($subtrees);
    $response->setCallback('Drupal.toolbar.setSubtrees.resolve');

    // The Expires HTTP header is the heart of the client-side HTTP caching. The
    // additional server-side page cache only takes effect when the client
    // accesses the callback URL again (e.g., after clearing the browser cache
    // or when force-reloading a Drupal page).
    $max_age = 365 * 24 * 60 * 60;
    $response->setPrivate();
    $response->setMaxAge($max_age);

    $expires = new \DateTime();
    $expires->setTimestamp(REQUEST_TIME + $max_age);
    $response->setExpires($expires);

    return $response;
  }

  /**
   * Checks access for the subtree controller.
   *
   * @param string $hash
   *   The hash of the toolbar subtrees.
   * @param string $langcode
   *   The langcode of the requested site, NULL if none given.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkSubTreeAccess($hash, $langcode) {
    return AccessResult::allowedIf($this->currentUser()->hasPermission('access toolbar') && $hash == _toolbar_get_subtrees_hash($langcode))->cachePerPermissions();
  }

}

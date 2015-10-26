<?php

/**
 * @file
 * Contains \Drupal\toolbar\Controller\ToolbarController.
 */

namespace Drupal\toolbar\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\toolbar\Ajax\SetSubtreesCommand;

/**
 * Defines a controller for the toolbar module.
 */
class ToolbarController extends ControllerBase {

  /**
   * Returns an AJAX response to render the toolbar subtrees.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function subtreesAjax() {
    list($subtrees, $cacheability) = toolbar_get_rendered_subtrees();
    $response = new AjaxResponse();
    $response->addCommand(new SetSubtreesCommand($subtrees));

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
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkSubTreeAccess($hash) {
    $expected_hash = _toolbar_get_subtrees_hash()[0];
    return AccessResult::allowedIf($this->currentUser()->hasPermission('access toolbar') && Crypt::hashEquals($expected_hash, $hash))->cachePerPermissions();
  }

}

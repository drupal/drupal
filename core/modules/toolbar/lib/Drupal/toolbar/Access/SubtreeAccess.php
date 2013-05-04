<?php

/**
 * @file
 * Contains \Drupal\toolbar\Access\SubtreeAccess.
 */

namespace Drupal\toolbar\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a special access checker for the toolbar subtree route.
 */
class SubtreeAccess implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_toolbar_subtree', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $hash = $request->get('hash');
    if (user_access('access toolbar') && ($hash == _toolbar_get_subtree_hash())) {
      return TRUE;
    }
    else {
      return NULL;
    }
  }

}

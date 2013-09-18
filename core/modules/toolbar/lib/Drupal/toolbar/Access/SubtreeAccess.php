<?php

/**
 * @file
 * Contains \Drupal\toolbar\Access\SubtreeAccess.
 */

namespace Drupal\toolbar\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a special access checker for the toolbar subtree route.
 */
class SubtreeAccess implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_toolbar_subtree');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $hash = $request->get('hash');
    return (user_access('access toolbar') && ($hash == _toolbar_get_subtrees_hash())) ? static::ALLOW : static::DENY;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\filter\Access\FilterAccessCheck.
 */

namespace Drupal\filter\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for text formats.
 */
class FilterAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_filter_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if ($format = $request->attributes->get('filter_format')) {
      // Handle special cases up front. All users have access to the fallback
      // format.
      if ($format->format == filter_fallback_format()) {
        return TRUE;
      }

      // Check the permission if one exists; otherwise, we have a non-existent
      // format so we return FALSE.
      $permission = filter_permission_name($format);
      return !empty($permission) && user_access($permission);
    }
  }
}

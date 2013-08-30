<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\ViewModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class ViewModeAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_field_ui_view_mode_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if ($entity_type = $request->attributes->get('entity_type')) {
      $bundle = $request->attributes->get('bundle');
      $view_mode = $request->attributes->get('mode');

      $view_mode_settings = field_view_mode_settings($entity_type, $bundle);
      $visibility = ($view_mode == 'default') || !empty($view_mode_settings[$view_mode]['status']);
      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_view_mode_access');
        return user_access($permission) ? static::ALLOW : static::DENY;
      }
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\ViewModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
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
  public function access(Route $route, Request $request, AccountInterface $account) {
    if ($entity_type = $request->attributes->get('entity_type')) {
      $bundle = $request->attributes->get('bundle');
      $view_mode = $request->attributes->get('mode');

      if ($view_mode == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_display = entity_load('entity_display', $entity_type . '.' . $bundle . '.' . $view_mode)) {
        $visibility = $entity_display->status();
      }
      else {
        $visibility = FALSE;
      }

      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_view_mode_access');
        return $account->hasPermission($permission) ? static::ALLOW : static::DENY;
      }
    }
  }

}

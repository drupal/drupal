<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\FormModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class FormModeAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_field_ui_form_mode_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if ($entity_type = $request->attributes->get('entity_type')) {
      $bundle = $request->attributes->get('bundle');
      $form_mode = $request->attributes->get('mode');

      if ($form_mode == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_form_display = entity_load('entity_form_display', $entity_type . '.' . $bundle . '.' . $form_mode)) {
        $visibility = $entity_form_display->status();
      }
      else {
        $visibility = FALSE;
      }

      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_form_mode_access');
        return user_access($permission) ? static::ALLOW : static::DENY;
      }
    }
  }

}

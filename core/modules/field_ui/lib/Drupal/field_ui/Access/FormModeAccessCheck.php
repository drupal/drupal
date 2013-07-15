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

      $form_mode_settings = field_form_mode_settings($entity_type, $bundle);
      $visibility = ($form_mode == 'default') || !empty($form_mode_settings[$form_mode]['status']);
      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_form_mode_access');
        return user_access($permission);
      }
    }
  }

}

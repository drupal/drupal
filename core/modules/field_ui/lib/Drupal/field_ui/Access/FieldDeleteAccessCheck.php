<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\FieldDeleteAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class FieldDeleteAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $field_instance = $request->attributes->get('field_instance_config');
    if (!$field_instance->getField()->isLocked()) {
      $permission = $route->getRequirement('_field_ui_field_delete_access');
      return $account->hasPermission($permission) ? static::ALLOW : static::DENY;
    }

    return static::DENY;
  }

}

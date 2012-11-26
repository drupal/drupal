<?php

/**
 * @file
 * Contains of Drupal\user\UserRouteController.
 */

namespace Drupal\user;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Returns responses for User module routes.
 */
class UserRouteController extends ContainerAware {

  /**
   * Returns the user registration form.
   *
   * @return array
   *   A renderable array containing the user registration form.
   */
  public function register() {
    $account = entity_create('user', array());
    return entity_get_form($account, 'register');
  }

}

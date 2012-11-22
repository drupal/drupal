<?php

/**
 * @file
 * Contains of Drupal\user\UserRouteController.
 */

namespace Drupal\user;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
    // @todo Remove once access control is integrated with new routing system:
    //   http://drupal.org/node/1793520.
    if (!user_register_access()) {
      throw new AccessDeniedHttpException();
    }

    $account = entity_create('user', array());
    return entity_get_form($account, 'register');
  }

}

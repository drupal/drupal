<?php

/**
 * @file
 * Contains \Drupal\user\UserBCDecorator.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityBCDecorator;

/**
 * Defines the user specific entity BC decorator.
 */
class UserBCDecorator extends EntityBCDecorator implements UserInterface {

  /**
   * {@inheritdoc}
   */
  public function &__get($name) {
    // Special handling for roles, as the return value is expected to be an
    // array.
    if ($name == 'roles') {
      $roles = array();
      foreach ($this->getNGEntity()->roles as $role) {
        $roles[] = $role->value;
      }
      return $roles;
    }
    return parent::__get($name);
  }
}

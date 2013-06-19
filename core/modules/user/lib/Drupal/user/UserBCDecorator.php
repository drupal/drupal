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
      $roles = $this->decorated->getRoles();
      return $roles;
    }
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->decorated->getRoles();
  }

  /**
   * {@inheritdoc}
   */
  public function getSecureSessionId() {
    return $this->decorated->getSecureSessionId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionData() {
    return $this->decorated->getSecureSessionId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->decorated->getSessionId();
  }

  /**
   * {@inheritdoc}
   */
  public function hasRole($rid) {
    return $this->decorated->hasRole($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($rid) {
    $this->decorated->addRole($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($rid) {
    $this->decorated->removeRole($rid);
  }

}

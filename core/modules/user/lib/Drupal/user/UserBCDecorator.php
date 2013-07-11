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
   * The decorated user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $decorated;

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

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->decorated->getPassword();
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword($password) {
    $this->decorated->setPassword($password);
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->decorated->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->decorated->setEmail($mail);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultTheme() {
    return $this->decorated->getDefaultTheme();
  }

  /**
   * {@inheritdoc}
   */
  public function getSignature() {
    return $this->decorated->getDefaultTheme();
  }

  /**
   * {@inheritdoc}
   */
  public function getSignatureFormat() {
    return $this->decorated->getSignatureFormat();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->decorated->getCreatedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->decorated->getLastAccessedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setLastAccessTime($timestamp) {
    $this->decorated->setLastAccessTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastLoginTime() {
    return $this->decorated->getLastLoginTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setLastLoginTime($timestamp) {
    $this->decorated->setLastLoginTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    $this->decorated->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function isBlocked() {
    return $this->decorated->isBlocked();
  }

  /**
   * {@inheritdoc}
   */
  public function activate() {
    return $this->decorated->activate();
  }

  /**
   * {@inheritdoc}
   */
  public function block() {
    return $this->decorated->block();
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
    return $this->decorated->getTimeZone();
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($default = NULL) {
    return $this->decorated->getPreferredLangcode($default);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($default = NULL) {
    return $this->decorated->getPreferredAdminLangcode($default);
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialEmail() {
    return $this->decorated->getInitialEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->decorated->id() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->decorated->id() == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->decorated->getUsername();
  }


}

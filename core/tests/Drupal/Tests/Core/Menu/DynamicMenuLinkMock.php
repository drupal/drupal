<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\DynamicMenuLinkMock.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines a mock implementation of a dynamic menu link used in tests only.
 *
 * Has a dynamic route and title. This is rather contrived, but there are valid
 * use cases.
 *
 * @see \Drupal\user\Plugin\Menu\LoginLogoutMenuLink
 */
class DynamicMenuLinkMock extends MenuLinkMock {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Sets the current user.
   *
   * Allows the menu link to return the right title and route.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if ($this->currentUser->isAuthenticated()) {
      return 'Log out';
    }
    else {
      return 'Log in';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    if ($this->currentUser->isAuthenticated()) {
      return 'user.logout';
    }
    else {
      return 'user.login';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.roles:authenticated'];
  }

}

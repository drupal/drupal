<?php

/**
 * @file
 * Contains \Drupal\user\UserConfigContext
 */

namespace Drupal\user;

use Drupal\Core\Config\Context\ConfigContext;
use Drupal\user\Plugin\Core\Entity\User;


/**
 * Defines a configuration context object for a user account.
 *
 * This should be used when configuration objects need a context for a user
 * other than the current user.
 *
 * @see user_mail()
 */
class UserConfigContext extends ConfigContext {

  /**
   * Predefined key for account object.
   */
  const USER_KEY = 'user.account';

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::setUuid().
   */
  public function setUuid() {
    // Use the user's uuid to identify the config context.
    $this->uuid = $this->get(self::USER_KEY)->uuid();
  }

  /*
   * Helper function to create config context for user accounts.
   *
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   The account to add to the config context.
   *
   * @return \Drupal\user\UserConfigContext
   *   The user config context object.
   */
  public function setAccount(User $account) {
    $this->init(self::USER_KEY, $account);
    return $this;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\user\UserConfigContext.
 */

namespace Drupal\user;

use Drupal\Core\Config\Context\ConfigContext;
use Drupal\user\UserInterfaceInterface;


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
   * Creates the configuration context for user accounts.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to add to the config context.
   *
   * @return \Drupal\user\UserConfigContext
   *   The user config context object.
   */
  public function setAccount(UserInterface $account) {
    $this->set(self::USER_KEY, $account);
    // Re-initialize since the user change changes the context fundamentally.
    $this->init();
    return $this;
  }

}

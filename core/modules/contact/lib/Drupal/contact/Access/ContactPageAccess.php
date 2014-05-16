<?php

/**
 * @file
 * Contains \Drupal\contact\Access\ContactPageAccess.
 */

namespace Drupal\contact\Access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Access check for contact_personal_page route.
 */
class ContactPageAccess implements AccessInterface {

  /**
   * The contact settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface;
   */
  protected $userData;

  /**
   * Constructs a ContactPageAccess instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserDataInterface $user_data) {
    $this->configFactory = $config_factory;
    $this->userData = $user_data;
  }

  /**
   * Checks access to the given user's contact page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being contacted.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    $contact_account = $user;

    // Anonymous users cannot have contact forms.
    if ($contact_account->isAnonymous()) {
      return static::DENY;
    }

    // Users may not contact themselves.
    if ($account->id() == $contact_account->id()) {
      return static::DENY;
    }

    // User administrators should always have access to personal contact forms.
    if ($account->hasPermission('administer users')) {
      return static::ALLOW;
    }

    // If requested user has been blocked, do not allow users to contact them.
    if ($contact_account->isBlocked()) {
      return static::DENY;
    }

    // If the requested user has disabled their contact form, do not allow users
    // to contact them.
    $account_data = $this->userData->get('contact', $contact_account->id(), 'enabled');
    if (isset($account_data) && empty($account_data)) {
      return static::DENY;
    }
    // If the requested user did not save a preference yet, deny access if the
    // configured default is disabled.
    else if (!$this->configFactory->get('contact.settings')->get('user_default_enabled')) {
      return static::DENY;
    }

    return $account->hasPermission('access user contact forms') ? static::ALLOW : static::DENY;
  }

}

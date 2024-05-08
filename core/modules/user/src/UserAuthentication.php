<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Validates user authentication credentials.
 */
class UserAuthentication implements UserAuthInterface, UserAuthenticationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Password\PasswordInterface $password_checker
   *   The password service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PasswordInterface $password_checker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->passwordChecker = $password_checker;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, #[\SensitiveParameter] $password) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:10.3.0 and will be removed from drupal:12.0.0. Implement \Drupal\user\UserAuthenticationInterface instead. See https://www.drupal.org/node/3411040');
    $uid = FALSE;

    if (!empty($username) && strlen($password) > 0) {
      $account_search = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]);

      if ($account = reset($account_search)) {
        if ($this->authenticateAccount($account, $password)) {
          $uid = $account->id();
        }
      }
    }
    return $uid;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupAccount($identifier): UserInterface|false {
    if (!empty($identifier)) {
      $account_search = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $identifier]);

      if ($account = reset($account_search)) {
        return $account;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticateAccount(UserInterface $account, #[\SensitiveParameter] string $password): bool {
    if ($this->passwordChecker->check($password, $account->getPassword())) {
      // Update user to new password scheme if needed.
      if ($this->passwordChecker->needsRehash($account->getPassword())) {
        $account->setPassword($password);
        $account->save();
      }
      return TRUE;
    }
    return FALSE;
  }

}

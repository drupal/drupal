<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Validates user authentication credentials.
 */
class UserAuthentication implements UserAuthenticationInterface {

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Password\PasswordInterface $passwordChecker
   *   The password service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PasswordInterface $passwordChecker,
  ) {
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

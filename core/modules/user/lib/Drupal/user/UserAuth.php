<?php

/**
 * @file
 * Contains \Drupal\user\UserAuth.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Validates user authentication credentials.
 */
class UserAuth implements UserAuthInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The user storage.
   * @param \Drupal\Core\Password\PasswordInterface $password_checker
   *   The password service.
   */
  public function __construct(EntityManagerInterface $entity_manager, PasswordInterface $password_checker) {
    $this->entityManager = $entity_manager;
    $this->passwordChecker = $password_checker;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
    $uid = FALSE;

    if (!empty($username) && !empty($password)) {
      $account_search = $this->entityManager->getStorage('user')->loadByProperties(array('name' => $username));

      if ($account = reset($account_search)) {
        if ($this->passwordChecker->check($password, $account)) {
          // Successful authentication.
          $uid = $account->id();

          // Update user to new password scheme if needed.
          if ($this->passwordChecker->userNeedsNewHash($account)) {
            $account->setPassword($password);
            $account->save();
          }
        }
      }
    }

    return $uid;
  }

}

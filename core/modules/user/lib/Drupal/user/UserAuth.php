<?php

/**
 * @file
 * Contains \Drupal\user\UserAuth.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Validates user authentication credentials.
 */
class UserAuth implements UserAuthInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storage;

  /**
   * The password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The user storage.
   * @param \Drupal\Core\Password\PasswordInterface $password_checker
   *   The password service.
   */
  public function __construct(EntityManagerInterface $entity_manager, PasswordInterface $password_checker) {
    $this->storage = $entity_manager->getStorageController('user');
    $this->passwordChecker = $password_checker;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
    $uid = FALSE;

    if (!empty($username) && !empty($password)) {
      $account_search = $this->storage->loadByProperties(array('name' => $username));

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

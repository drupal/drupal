<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Access\AccessException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;

/**
 * @internal
 *   This API is experimental.
 */
final class AdminAccountSwitcher implements AccountSwitcherInterface {

  public function __construct(
    private readonly AccountSwitcherInterface $decorated,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly bool $isSuperUserAccessEnabled,
  ) {}

  /**
   * Switches to an administrative account.
   *
   * This will switch to the first available account with a role that has the
   * `is_admin` flag. If there are no such roles, or no such users, this will
   * try to switch to user 1 if superuser access is enabled.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account that was switched to.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown if there are no users with administrative roles.
   */
  public function switchToAdministrator(): AccountInterface {
    $admin_roles = $this->entityTypeManager->getStorage('user_role')
      ->getQuery()
      ->condition('is_admin', TRUE)
      ->execute();

    $user_storage = $this->entityTypeManager->getStorage('user');

    if ($admin_roles) {
      $accounts = $user_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', $admin_roles, 'IN')
        ->condition('status', 1)
        ->sort('uid')
        ->range(0, 1)
        ->execute();
    }
    else {
      $accounts = [];
    }
    $account = $user_storage->load(reset($accounts) ?: 1);
    assert($account instanceof AccountInterface);

    if (array_intersect($account->getRoles(), $admin_roles) || ((int) $account->id() === 1 && $this->isSuperUserAccessEnabled)) {
      $this->switchTo($account);
      return $account;
    }
    throw new AccessException("There are no user accounts with administrative roles.");
  }

  /**
   * {@inheritdoc}
   */
  public function switchTo(AccountInterface $account): AccountSwitcherInterface {
    $this->decorated->switchTo($account);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function switchBack(): AccountSwitcherInterface {
    $this->decorated->switchBack();
    return $this;
  }

}

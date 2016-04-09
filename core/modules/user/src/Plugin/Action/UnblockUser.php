<?php

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unblocks a user.
 *
 * @Action(
 *   id = "user_unblock_user_action",
 *   label = @Translation("Unblock the selected users"),
 *   type = "user"
 * )
 */
class UnblockUser extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Skip unblocking user if they are already unblocked.
    if ($account !== FALSE && $account->isBlocked()) {
      $account->activate();
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}

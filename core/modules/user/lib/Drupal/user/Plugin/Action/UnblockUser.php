<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Action\UnblockUser.
 */

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\ActionBase;

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

}

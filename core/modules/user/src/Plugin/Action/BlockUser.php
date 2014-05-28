<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Action\BlockUser.
 */

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Blocks a user.
 *
 * @Action(
 *   id = "user_block_user_action",
 *   label = @Translation("Block the selected users"),
 *   type = "user"
 * )
 */
class BlockUser extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Skip blocking user if they are already blocked.
    if ($account !== FALSE && $account->isActive()) {
      // For efficiency manually save the original account before applying any
      // changes.
      $account->original = clone $account;
      $account->block();
      $account->save();
    }
  }

}

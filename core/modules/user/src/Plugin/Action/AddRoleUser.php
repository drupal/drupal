<?php

namespace Drupal\user\Plugin\Action;

/**
 * Adds a role to a user.
 *
 * @Action(
 *   id = "user_add_role_action",
 *   label = @Translation("Add a role to the selected users"),
 *   type = "user"
 * )
 */
class AddRoleUser extends ChangeUserRoleBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    $rid = $this->configuration['rid'];
    // Skip adding the role to the user if they already have it.
    if ($account !== FALSE && !$account->hasRole($rid)) {
      // For efficiency manually save the original account before applying
      // any changes.
      $account->original = clone $account;
      $account->addRole($rid);
      $account->save();
    }
  }

}

<?php

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Adds a role to a user.
 */
#[Action(
  id: 'user_add_role_action',
  label: new TranslatableMarkup('Add a role to the selected users'),
  type: 'user'
)]
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
      $account->setOriginal(clone $account);
      $account->addRole($rid)->save();
    }
  }

}

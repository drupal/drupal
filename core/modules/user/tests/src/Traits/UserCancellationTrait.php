<?php

namespace Drupal\Tests\user\Traits;

use Drupal\user\UserInterface;

/**
 * Provides methods to cancel user accounts in the UI.
 */
trait UserCancellationTrait {

  /**
   * Cancels a user account via the UI.
   *
   * @param \Drupal\user\UserInterface $user
   *   The account to cancel.
   * @param string $method
   *   The cancellation method. Should be one of the METHOD_* constants of
   *   \Drupal\user\CancellationHandlerInterface.
   */
  protected function cancelUser(UserInterface $user, string $method): void {
    $page = $this->getSession()->getPage();

    $this->drupalGet('/admin/people');
    $page->checkField('Update the user ' . $user->getDisplayName());
    $page->selectFieldOption('action', 'user_cancel_user_action');
    $page->pressButton('Apply to selected items');
    $page->selectFieldOption('user_cancel_method', $method);
    $page->pressButton('Cancel accounts');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update has been performed.');
  }

}

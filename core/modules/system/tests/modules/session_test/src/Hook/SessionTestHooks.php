<?php

declare(strict_types=1);

namespace Drupal\session_test\Hook;

use Drupal\user\UserInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for session_test.
 */
class SessionTestHooks {

  /**
   * Implements hook_user_login().
   */
  #[Hook('user_login')]
  public function userLogin(UserInterface $account) {
    if ($account->getAccountName() == 'session_test_user') {
      // Exit so we can verify that the session was regenerated
      // before hook_user_login() was called.
      exit;
    }
    // Add some data in the session for retrieval testing purpose.
    \Drupal::request()->getSession()->set("session_test_key", "foobar");
  }

}

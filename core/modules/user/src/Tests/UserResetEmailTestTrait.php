<?php

namespace Drupal\user\Tests;

use Drupal\Core\Test\AssertMailTrait;

/**
 * Helper function for logging in from reset password email.
 */
trait UserResetEmailTestTrait {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Login from reset password email.
   */
  protected function loginFromResetEmail() {
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);
    $resetURL = $urls[0];
    $this->drupalGet($resetURL);
    $this->drupalPostForm(NULL, NULL, 'Log in');
  }

}

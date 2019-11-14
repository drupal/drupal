<?php

namespace Drupal\user\Tests;

@trigger_error(__NAMESPACE__ . '\UserResetEmailTestTrait is deprecated and scheduled for removal before Drupal 9.0.0. Add the method to the test class instead, see https://www.drupal.org/node/2999766', E_USER_DEPRECATED);

use Drupal\Core\Test\AssertMailTrait;

/**
 * Helper function for logging in from reset password email.
 *
 * @deprecated in drupal:8.?.? and is removed from drupal:9.0.0.
 *   Add the method to the test class instead.
 *
 * @see https://www.drupal.org/node/2999766
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

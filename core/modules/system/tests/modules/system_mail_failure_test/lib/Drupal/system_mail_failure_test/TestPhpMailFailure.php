<?php

/**
 * @file
 * Contains \Drupal\system_mail_failure_test\TestPhpMailFailure.
 */

namespace Drupal\system_mail_failure_test;

use Drupal\Core\Mail\PhpMail;
use Drupal\Core\Mail\MailInterface;

/**
 * Defines a mail sending implementation that returns false.
 *
 * This class is for running tests or for development. To use set the
 * configuration:
 * @code
 *   config('system.mail')->set('interface.default', 'Drupal\system_mail_failure_test\TestPhpMailFailure')->save();
 * @endcode
 */
class TestPhpMailFailure extends PhpMail implements MailInterface {

  /**
   * Overrides Drupal\Core\Mail\PhpMail::mail().
   */
  public function mail(array $message) {
    // Instead of attempting to send a message, just return failure.
    return FALSE;
  }
}

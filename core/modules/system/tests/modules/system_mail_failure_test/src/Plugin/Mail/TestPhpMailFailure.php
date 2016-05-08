<?php

namespace Drupal\system_mail_failure_test\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Mail\MailInterface;

/**
 * Defines a mail sending implementation that always fails.
 *
 * This class is for running tests or for development. To use set the
 * configuration:
 * @code
 *   \Drupal::configFactory()->getEditable('system.mail')->set('interface.default', 'test_php_mail_failure')->save();
 * @endcode
 *
 * @Mail(
 *   id = "test_php_mail_failure",
 *   label = @Translation("Malfunctioning mail backend"),
 *   description = @Translation("An intentionally broken mail backend, used for tests.")
 * )
 */
class TestPhpMailFailure extends PhpMail implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // Simulate a failed mail send by returning FALSE.
    return FALSE;
  }

}

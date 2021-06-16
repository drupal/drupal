<?php

namespace Drupal\Core\Mail\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Defines a mail backend that captures sent messages in the state system.
 *
 * This class is for running tests or for development.
 *
 * @Mail(
 *   id = "test_mail_collector",
 *   label = @Translation("Mail collector"),
 *   description = @Translation("Does not send the message, but stores it in Drupal within the state system. Used for testing.")
 * )
 */
class TestMailCollector extends PhpMail implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $captured_emails = \Drupal::state()->get('system.test_mail_collector', []);
    $captured_emails[] = $message;
    \Drupal::state()->set('system.test_mail_collector', $captured_emails);

    return TRUE;
  }

}

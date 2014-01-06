<?php

/**
 * @file
 * Contains \Drupal\Core\Mail\TestMailCollector.
 */

namespace Drupal\Core\Mail;

/**
 * Defines a mail sending implementation that captures sent messages to the
 * state system.
 *
 * This class is for running tests or for development.
 */
class TestMailCollector extends PhpMail implements MailInterface {

  /**
   * Overrides \Drupal\Core\Mail\PhpMail::mail().
   *
   * Accepts an e-mail message and stores it with the state system.
   */
  public function mail(array $message) {
    $captured_emails = \Drupal::state()->get('system.test_mail_collector') ?: array();
    $captured_emails[] = $message;
    \Drupal::state()->set('system.test_mail_collector', $captured_emails);

    return TRUE;
  }
}

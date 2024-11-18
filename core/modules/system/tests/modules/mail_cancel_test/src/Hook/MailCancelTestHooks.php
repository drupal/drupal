<?php

declare(strict_types=1);

namespace Drupal\mail_cancel_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for mail_cancel_test.
 */
class MailCancelTestHooks {

  /**
   * Implements hook_mail_alter().
   *
   * Aborts sending of messages with ID 'mail_cancel_test_cancel_test'.
   *
   * @see MailTestCase::testCancelMessage()
   */
  #[Hook('mail_alter')]
  public function mailAlter(&$message): void {
    if ($message['id'] == 'mail_cancel_test_cancel_test') {
      $message['send'] = FALSE;
    }
  }

}

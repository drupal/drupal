<?php

declare(strict_types=1);

namespace Drupal\Core\Test;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Provides methods for testing emails sent during test runs.
 *
 * @see \Drupal\mailer_capture\Transport\CaptureTransport
 */
trait MailerCaptureTrait {

  /**
   * Gets an array containing all emails sent during this test case.
   *
   * @return \Symfony\Component\Mime\Email[]
   *   An array containing email messages captured during the current test.
   */
  protected function getEmails(): array {
    $messages = array_map(fn (SentMessage $m) => $m->getOriginalMessage(), $this->getCapturedMessages());
    return array_filter($messages, fn (RawMessage $m) => $m instanceof Email);
  }

  /**
   * Gets an array containing all messages sent during this test case.
   *
   * @return \Symfony\Component\Mailer\SentMessage[]
   *   An array containing messages captured during the current test.
   */
  protected function getCapturedMessages(): array {
    return \Drupal::keyValue('mailer_capture')->get('messages', []);
  }

  /**
   * Clears all messages sent during this test case.
   */
  protected function clearCapturedMessages(): void {
    \Drupal::keyValue('mailer_capture')->delete('messages');
  }

}

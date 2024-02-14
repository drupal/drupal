<?php

namespace Drupal\mail_html_test\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a mail backend that captures sent HTML messages in the state system.
 *
 * This class is for running tests or for development and does not convert HTML
 * to plaintext.
 */
#[Mail(
  id: 'test_html_mail_collector',
  label: new TranslatableMarkup('HTML mail collector'),
  description: new TranslatableMarkup('Does not send the message, but stores its HTML in Drupal within the state system. Used for testing.'),
)]
class TestHtmlMailCollector extends TestMailCollector {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode(PHP_EOL, $message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);
    return $message;
  }

}

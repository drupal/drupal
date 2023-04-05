<?php

namespace Drupal\FunctionalTests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests the collection of emails during testing.
 *
 * The test mail collector, test.mail.collector, intercepts any email sent
 * during a test so it does not leave the test server.
 *
 * @group browsertestbase
 */
class MailCaptureTest extends BrowserTestBase {
  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests to see if the wrapper function is executed correctly.
   */
  public function testMailSend() {
    // Create an email.
    $subject = $this->randomString(64);
    $body = $this->randomString(128);
    $message = [
      'id' => 'drupal_mail_test',
      'headers' => ['Content-type' => 'text/html'],
      'subject' => $subject,
      'to' => 'foobar@example.com',
      'body' => $body,
    ];

    // Before we send the email, drupalGetMails should return an empty array.
    $captured_emails = $this->drupalGetMails();
    $this->assertCount(0, $captured_emails, 'The captured emails queue is empty.');

    // Send the email.
    \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'simpletest', 'key' => 'drupal_mail_test'])->mail($message);

    // Ensure that there is one email in the captured emails array.
    $captured_emails = $this->drupalGetMails();
    $this->assertCount(1, $captured_emails, 'One email was captured.');

    // Assert that the email was sent by iterating over the message properties
    // and ensuring that they are captured intact.
    foreach ($message as $field => $value) {
      $this->assertMail($field, $value, new FormattableMarkup('The email was sent and the value for property @field is intact.', ['@field' => $field]), 'Email');
    }

    // Send additional emails so more than one email is captured.
    for ($index = 0; $index < 5; $index++) {
      $message = [
        'id' => 'drupal_mail_test_' . $index,
        'headers' => ['Content-type' => 'text/html'],
        'subject' => $this->randomString(64),
        'to' => $this->randomMachineName(32) . '@example.com',
        'body' => $this->randomString(512),
      ];
      \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'drupal_mail_test', 'key' => $index])->mail($message);
    }

    // There should now be 6 emails captured.
    $captured_emails = $this->drupalGetMails();
    $this->assertCount(6, $captured_emails, 'All emails were captured.');

    // Test different ways of getting filtered emails via drupalGetMails().
    $captured_emails = $this->drupalGetMails(['id' => 'drupal_mail_test']);
    $this->assertCount(1, $captured_emails, 'Only one email is returned when filtering by id.');
    $captured_emails = $this->drupalGetMails(['id' => 'drupal_mail_test', 'subject' => $subject]);
    $this->assertCount(1, $captured_emails, 'Only one email is returned when filtering by id and subject.');
    $captured_emails = $this->drupalGetMails(['id' => 'drupal_mail_test', 'subject' => $subject, 'from' => 'this_was_not_used@example.com']);
    $this->assertCount(0, $captured_emails, 'No emails are returned when querying with an unused from address.');

    // Send the last email again, so we can confirm that the
    // drupalGetMails-filter correctly returns all emails with a given
    // property/value.
    \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'drupal_mail_test', 'key' => $index])->mail($message);
    $captured_emails = $this->drupalGetMails(['id' => 'drupal_mail_test_4']);
    $this->assertCount(2, $captured_emails, 'All emails with the same id are returned when filtering by id.');
  }

}

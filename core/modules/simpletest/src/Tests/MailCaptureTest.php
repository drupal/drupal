<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the SimpleTest email capturing logic, the assertMail assertion and the
 * drupalGetMails function.
 *
 * @group simpletest
 */
class MailCaptureTest extends WebTestBase {
  /**
   * Test to see if the wrapper function is executed correctly.
   */
  function testMailSend() {
    // Create an email.
    $subject = $this->randomString(64);
    $body = $this->randomString(128);
    $message = array(
      'id' => 'drupal_mail_test',
      'headers' => array('Content-type'=> 'text/html'),
      'subject' => $subject,
      'to' => 'foobar@example.com',
      'body' => $body,
    );

    // Before we send the email, drupalGetMails should return an empty array.
    $captured_emails = $this->drupalGetMails();
    $this->assertEqual(count($captured_emails), 0, 'The captured emails queue is empty.', 'Email');

    // Send the email.
    \Drupal::service('plugin.manager.mail')->getInstance(array('module' => 'simpletest', 'key' => 'drupal_mail_test'))->mail($message);

    // Ensure that there is one email in the captured emails array.
    $captured_emails = $this->drupalGetMails();
    $this->assertEqual(count($captured_emails), 1, 'One email was captured.', 'Email');

    // Assert that the email was sent by iterating over the message properties
    // and ensuring that they are captured intact.
    foreach ($message as $field => $value) {
      $this->assertMail($field, $value, format_string('The email was sent and the value for property @field is intact.', array('@field' => $field)), 'Email');
    }

    // Send additional emails so more than one email is captured.
    for ($index = 0; $index < 5; $index++) {
      $message = array(
        'id' => 'drupal_mail_test_' . $index,
        'headers' => array('Content-type'=> 'text/html'),
        'subject' => $this->randomString(64),
        'to' => $this->randomMachineName(32) . '@example.com',
        'body' => $this->randomString(512),
      );
      \Drupal::service('plugin.manager.mail')->getInstance(array('module' => 'drupal_mail_test', 'key' => $index))->mail($message);
    }

    // There should now be 6 emails captured.
    $captured_emails = $this->drupalGetMails();
    $this->assertEqual(count($captured_emails), 6, 'All emails were captured.', 'Email');

    // Test different ways of getting filtered emails via drupalGetMails().
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test'));
    $this->assertEqual(count($captured_emails), 1, 'Only one email is returned when filtering by id.', 'Email');
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test', 'subject' => $subject));
    $this->assertEqual(count($captured_emails), 1, 'Only one email is returned when filtering by id and subject.', 'Email');
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test', 'subject' => $subject, 'from' => 'this_was_not_used@example.com'));
    $this->assertEqual(count($captured_emails), 0, 'No emails are returned when querying with an unused from address.', 'Email');

    // Send the last email again, so we can confirm that the
    // drupalGetMails-filter correctly returns all emails with a given
    // property/value.
    \Drupal::service('plugin.manager.mail')->getInstance(array('module' => 'drupal_mail_test', 'key' => $index))->mail($message);
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test_4'));
    $this->assertEqual(count($captured_emails), 2, 'All emails with the same id are returned when filtering by id.', 'Email');
  }
}

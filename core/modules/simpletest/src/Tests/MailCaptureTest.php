<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\MailCaptureTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Simpletest email capturing (TestMailCollector) and assertion methods.
 */
class MailCaptureTest extends WebTestBase {
  /**
   * Implement getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'SimpleTest e-mail capturing',
      'description' => 'Test the SimpleTest e-mail capturing logic, the assertMail assertion and the drupalGetMails function.',
      'group' => 'SimpleTest',
    );
  }

  /**
   * Test to see if the wrapper function is executed correctly.
   */
  function testMailSend() {
    // Create an e-mail.
    $subject = $this->randomString(64);
    $body = $this->randomString(128);
    $message = array(
      'id' => 'drupal_mail_test',
      'headers' => array('Content-type'=> 'text/html'),
      'subject' => $subject,
      'to' => 'foobar@example.com',
      'body' => $body,
    );

    // Before we send the e-mail, drupalGetMails should return an empty array.
    $captured_emails = $this->drupalGetMails();
    $this->assertEqual(count($captured_emails), 0, 'The captured e-mails queue is empty.', 'E-mail');

    // Send the e-mail.
    drupal_mail_system('simpletest', 'drupal_mail_test')->mail($message);

    // Ensure that there is one e-mail in the captured e-mails array.
    $captured_emails = $this->drupalGetMails();
    $this->assertEqual(count($captured_emails), 1, 'One e-mail was captured.', 'E-mail');

    // Assert that the e-mail was sent by iterating over the message properties
    // and ensuring that they are captured intact.
    foreach ($message as $field => $value) {
      $this->assertMail($field, $value, format_string('The e-mail was sent and the value for property @field is intact.', array('@field' => $field)), 'E-mail');
    }

    // Send additional e-mails so more than one e-mail is captured.
    for ($index = 0; $index < 5; $index++) {
      $message = array(
        'id' => 'drupal_mail_test_' . $index,
        'headers' => array('Content-type'=> 'text/html'),
        'subject' => $this->randomString(64),
        'to' => $this->randomName(32) . '@example.com',
        'body' => $this->randomString(512),
      );
      drupal_mail_system('drupal_mail_test', $index)->mail($message);
    }

    // There should now be 6 e-mails captured.
    $captured_emails = $this->drupalGetMails();
    $this->assertEqual(count($captured_emails), 6, 'All e-mails were captured.', 'E-mail');

    // Test different ways of getting filtered e-mails via drupalGetMails().
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test'));
    $this->assertEqual(count($captured_emails), 1, 'Only one e-mail is returned when filtering by id.', 'E-mail');
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test', 'subject' => $subject));
    $this->assertEqual(count($captured_emails), 1, 'Only one e-mail is returned when filtering by id and subject.', 'E-mail');
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test', 'subject' => $subject, 'from' => 'this_was_not_used@example.com'));
    $this->assertEqual(count($captured_emails), 0, 'No e-mails are returned when querying with an unused from address.', 'E-mail');

    // Send the last e-mail again, so we can confirm that the
    // drupalGetMails-filter correctly returns all e-mails with a given
    // property/value.
    drupal_mail_system('drupal_mail_test', $index)->mail($message);
    $captured_emails = $this->drupalGetMails(array('id' => 'drupal_mail_test_4'));
    $this->assertEqual(count($captured_emails), 2, 'All e-mails with the same id are returned when filtering by id.', 'E-mail');
  }
}

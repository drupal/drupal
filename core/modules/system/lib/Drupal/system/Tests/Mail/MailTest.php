<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Mail\MailTest.
 */

namespace Drupal\system\Tests\Mail;

use Drupal\Core\Language\Language;
use Drupal\Core\Mail\MailInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Defines a mail class used for testing.
 */
class MailTest extends WebTestBase implements MailInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  /**
   * The most recent message that was sent through the test case.
   *
   * We take advantage here of the fact that static variables are shared among
   * all instance of the same class.
   */
  private static $sent_message;

  public static function getInfo() {
    return array(
      'name' => 'Mail system',
      'description' => 'Performs tests on the pluggable mailing framework.',
      'group' => 'Mail',
    );
  }

  function setUp() {
    parent::setUp();

    // Set MailTestCase (i.e. this class) as the SMTP library
    \Drupal::config('system.mail')->set('interface.default', 'Drupal\system\Tests\Mail\MailTest')->save();
  }

  /**
   * Assert that the pluggable mail system is functional.
   */
  public function testPluggableFramework() {
    $language_interface = language(Language::TYPE_INTERFACE);

    // Use MailTestCase for sending a message.
    drupal_mail('simpletest', 'mail_test', 'testing@example.com', $language_interface->id);

    // Assert whether the message was sent through the send function.
    $this->assertEqual(self::$sent_message['to'], 'testing@example.com', 'Pluggable mail system is extendable.');
  }

  /**
   * Test that message sending may be canceled.
   *
   * @see simpletest_mail_alter()
   */
  public function testCancelMessage() {
    $language_interface = language(Language::TYPE_INTERFACE);

    // Reset the class variable holding a copy of the last sent message.
    self::$sent_message = NULL;

    // Send a test message that simpletest_mail_alter should cancel.
    drupal_mail('simpletest', 'cancel_test', 'cancel@example.com', $language_interface->id);

    // Assert that the message was not actually sent.
    $this->assertNull(self::$sent_message, 'Message was canceled.');
  }

  /**
   * Checks the From: and Reply-to: headers.
   */
  public function testFromAndReplyToHeader() {
    global $language;

    // Reset the class variable holding a copy of the last sent message.
    self::$sent_message = NULL;
    // Send an e-mail with a reply-to address specified.
    $from_email = 'Drupal <simpletest@example.com>';
    $reply_email = 'someone_else@example.com';
    drupal_mail('simpletest', 'from_test', 'from_test@example.com', $language, array(), $reply_email);
    // Test that the reply-to e-mail is just the e-mail and not the site name and
    // default sender e-mail.
    $this->assertEqual($from_email, self::$sent_message['headers']['From'], 'Message is sent from the site email account.');
    $this->assertEqual($reply_email, self::$sent_message['headers']['Reply-to'], 'Message reply-to headers are set.');
    $this->assertFalse(isset(self::$sent_message['headers']['Errors-To']), 'Errors-to header must not be set, it is deprecated.');

    self::$sent_message = NULL;
    // Send an e-mail and check that the From-header contains the site name.
    drupal_mail('simpletest', 'from_test', 'from_test@example.com', $language);
    $this->assertEqual($from_email, self::$sent_message['headers']['From'], 'Message is sent from the site email account.');
    $this->assertFalse(isset(self::$sent_message['headers']['Reply-to']), 'Message reply-to is not set if not specified.');
    $this->assertFalse(isset(self::$sent_message['headers']['Errors-To']), 'Errors-to header must not be set, it is deprecated.');
  }

  /**
   * Concatenate and wrap the e-mail body for plain-text mails.
   *
   * @see \Drupal\Core\Mail\PhpMail
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    // Convert any HTML to plain-text.
    $message['body'] = drupal_html_to_text($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = drupal_wrap_mail($message['body']);
    return $message;
  }

  /**
   * Send function that is called through the mail system.
   */
  public function mail(array $message) {
    self::$sent_message = $message;
  }
}

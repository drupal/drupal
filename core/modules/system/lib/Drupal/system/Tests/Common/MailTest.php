<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\MailTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Mail\MailInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Defines a mail class used for testing.
 */
class MailTest extends WebTestBase implements MailInterface {
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
    parent::setUp(array('simpletest'));

    // Set MailTestCase (i.e. this class) as the SMTP library
    variable_set('mail_system', array('default-system' => 'Drupal\system\Tests\Common\MailTest'));
  }

  /**
   * Assert that the pluggable mail system is functional.
   */
  public function testPluggableFramework() {
    global $language_interface;

    // Use MailTestCase for sending a message.
    $message = drupal_mail('simpletest', 'mail_test', 'testing@example.com', $language_interface);

    // Assert whether the message was sent through the send function.
    $this->assertEqual(self::$sent_message['to'], 'testing@example.com', t('Pluggable mail system is extendable.'));
  }

  /**
   * Test that message sending may be canceled.
   *
   * @see simpletest_mail_alter()
   */
  public function testCancelMessage() {
    global $language;

    // Reset the class variable holding a copy of the last sent message.
    self::$sent_message = NULL;

    // Send a test message that simpletest_mail_alter should cancel.
    $message = drupal_mail('simpletest', 'cancel_test', 'cancel@example.com', $language);

    // Assert that the message was not actually sent.
    $this->assertNull(self::$sent_message, 'Message was canceled.');
  }

  /**
   * Concatenate and wrap the e-mail body for plain-text mails.
   *
   * @see Drupal\Core\Mail\PhpMail
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

<?php

namespace Drupal\system\Tests\Mail;

use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;
use Drupal\simpletest\WebTestBase;
use Drupal\system_mail_failure_test\Plugin\Mail\TestPhpMailFailure;

/**
 * Performs tests on the pluggable mailing framework.
 *
 * @group Mail
 */
class MailTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest', 'system_mail_failure_test');

  /**
   * Assert that the pluggable mail system is functional.
   */
  public function testPluggableFramework() {
    // Switch mail backends.
    $this->config('system.mail')->set('interface.default', 'test_php_mail_failure')->save();

    // Get the default MailInterface class instance.
    $mail_backend = \Drupal::service('plugin.manager.mail')->getInstance(array('module' => 'default', 'key' => 'default'));

    // Assert whether the default mail backend is an instance of the expected
    // class.
    $this->assertTrue($mail_backend instanceof TestPhpMailFailure, 'Default mail interface can be swapped.');

    // Add a module-specific mail backend.
    $this->config('system.mail')->set('interface.mymodule_testkey', 'test_mail_collector')->save();

    // Get the added MailInterface class instance.
    $mail_backend = \Drupal::service('plugin.manager.mail')->getInstance(array('module' => 'mymodule', 'key' => 'testkey'));

    // Assert whether the added mail backend is an instance of the expected
    // class.
    $this->assertTrue($mail_backend instanceof TestMailCollector, 'Additional mail interfaces can be added.');
  }

  /**
   * Test that message sending may be canceled.
   *
   * @see simpletest_mail_alter()
   */
  public function testCancelMessage() {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Use the state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_mail_collector')->save();
    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', array());

    // Send a test message that simpletest_mail_alter should cancel.
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'cancel_test', 'cancel@example.com', $language_interface->getId());
    // Retrieve sent message.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);

    // Assert that the message was not actually sent.
    $this->assertFalse($sent_message, 'Message was canceled.');
  }

  /**
   * Checks the From: and Reply-to: headers.
   */
  public function testFromAndReplyToHeader() {
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // Use the state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_mail_collector')->save();
    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', array());
    // Send an email with a reply-to address specified.
    $from_email = 'Drupal <simpletest@example.com>';
    $reply_email = 'someone_else@example.com';
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language, array(), $reply_email);
    // Test that the reply-to email is just the email and not the site name
    // and default sender email.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $this->assertEqual($from_email, $sent_message['headers']['From'], 'Message is sent from the site email account.');
    $this->assertEqual($reply_email, $sent_message['headers']['Reply-to'], 'Message reply-to headers are set.');
    $this->assertFalse(isset($sent_message['headers']['Errors-To']), 'Errors-to header must not be set, it is deprecated.');

    // Send an email and check that the From-header contains the site name.
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $this->assertEqual($from_email, $sent_message['headers']['From'], 'Message is sent from the site email account.');
    $this->assertFalse(isset($sent_message['headers']['Reply-to']), 'Message reply-to is not set if not specified.');
    $this->assertFalse(isset($sent_message['headers']['Errors-To']), 'Errors-to header must not be set, it is deprecated.');
  }
}

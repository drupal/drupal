<?php
/**
 * @file
 * Contains \Drupal\contact\Tests\MessageEntityTest.
 */

namespace Drupal\contact\Tests;

use Drupal\contact\Entity\Message;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the message entity class.
 *
 * @see \Drupal\contact\Entity\Message
 */
class MessageEntityTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'contact', 'field', 'user');

  public static function getInfo() {
    return array(
      'name' => 'Message entity tests',
      'description' => 'Tests the message entity class.',
      'group' => 'Contact'
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('contact'));
  }

  /**
   * Test some of the methods.
   */
  public function testMessageMethods() {
    $message_storage = $this->container->get('entity.manager')->getStorageController('contact_message');
    $message = $message_storage->create(array('category' => 'feedback'));

    // Check for empty values first.
    $this->assertEqual($message->getMessage(), '');
    $this->assertEqual($message->getSenderName(), '');
    $this->assertEqual($message->getSenderMail(), '');
    $this->assertFalse($message->copySender());

    // Check for default values.
    $this->assertEqual('feedback', $message->getCategory()->id());
    $this->assertFalse($message->isPersonal());

    // Set some values and check for them afterwards.
    $message->setMessage('welcome_message');
    $message->setSenderName('sender_name');
    $message->setSenderMail('sender_mail');
    $message->setCopySender(TRUE);

    $this->assertEqual($message->getMessage(), 'welcome_message');
    $this->assertEqual($message->getSenderName(), 'sender_name');
    $this->assertEqual($message->getSenderMail(), 'sender_mail');
    $this->assertTrue($message->copySender());
  }

}

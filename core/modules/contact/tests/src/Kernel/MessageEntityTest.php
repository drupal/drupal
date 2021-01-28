<?php

namespace Drupal\Tests\contact\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the message entity class.
 *
 * @group contact
 * @see \Drupal\contact\Entity\Message
 */
class MessageEntityTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'contact',
    'field',
    'user',
    'contact_test',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['contact', 'contact_test']);
  }

  /**
   * Test some of the methods.
   */
  public function testMessageMethods() {
    $message_storage = $this->container->get('entity_type.manager')->getStorage('contact_message');
    $message = $message_storage->create(['contact_form' => 'feedback']);

    // Check for empty values first.
    $this->assertEqual('', $message->getMessage());
    $this->assertEqual('', $message->getSenderName());
    $this->assertEqual('', $message->getSenderMail());
    $this->assertFalse($message->copySender());

    // Check for default values.
    $this->assertEqual('feedback', $message->getContactForm()->id());
    $this->assertFalse($message->isPersonal());

    // Set some values and check for them afterwards.
    $message->setMessage('welcome_message');
    $message->setSenderName('sender_name');
    $message->setSenderMail('sender_mail');
    $message->setCopySender(TRUE);

    $this->assertEqual('welcome_message', $message->getMessage());
    $this->assertEqual('sender_name', $message->getSenderName());
    $this->assertEqual('sender_mail', $message->getSenderMail());
    $this->assertTrue($message->copySender());

    $no_access_user = $this->createUser(['uid' => 2]);
    $access_user = $this->createUser(['uid' => 3], ['access site-wide contact form']);
    $admin = $this->createUser(['uid' => 4], ['administer contact forms']);

    $this->assertFalse(\Drupal::entityTypeManager()->getAccessControlHandler('contact_message')->createAccess(NULL, $no_access_user));
    $this->assertTrue(\Drupal::entityTypeManager()->getAccessControlHandler('contact_message')->createAccess(NULL, $access_user));
    $this->assertTrue($message->access('edit', $admin));
    $this->assertFalse($message->access('edit', $access_user));
  }

}

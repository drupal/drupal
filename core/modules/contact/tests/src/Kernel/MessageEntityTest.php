<?php

declare(strict_types=1);

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['contact', 'contact_test']);
  }

  /**
   * Tests some of the methods.
   */
  public function testMessageMethods(): void {
    $message_storage = $this->container->get('entity_type.manager')->getStorage('contact_message');
    $message = $message_storage->create(['contact_form' => 'feedback']);

    // Check for empty values first.
    $this->assertEquals('', $message->getMessage());
    $this->assertEquals('', $message->getSenderName());
    $this->assertEquals('', $message->getSenderMail());
    $this->assertFalse($message->copySender());

    // Check for default values.
    $this->assertEquals('feedback', $message->getContactForm()->id());
    $this->assertFalse($message->isPersonal());

    // Set some values and check for them afterwards.
    $message->setMessage('welcome_message');
    $message->setSenderName('sender_name');
    $message->setSenderMail('sender_mail');
    $message->setCopySender(TRUE);

    $this->assertEquals('welcome_message', $message->getMessage());
    $this->assertEquals('sender_name', $message->getSenderName());
    $this->assertEquals('sender_mail', $message->getSenderMail());
    $this->assertTrue($message->copySender());

    $no_access_user = $this->createUser([], NULL, FALSE, ['uid' => 2]);
    $access_user = $this->createUser(['access site-wide contact form'], NULL, FALSE, ['uid' => 3]);
    $admin = $this->createUser(['administer contact forms'], NULL, FALSE, ['uid' => 4]);

    $this->assertFalse(\Drupal::entityTypeManager()->getAccessControlHandler('contact_message')->createAccess(NULL, $no_access_user));
    $this->assertTrue(\Drupal::entityTypeManager()->getAccessControlHandler('contact_message')->createAccess(NULL, $access_user));
    $this->assertTrue($message->access('update', $admin));
    $this->assertFalse($message->access('update', $access_user));
  }

}

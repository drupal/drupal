<?php

namespace Drupal\KernelTests\Core\Messenger;

use Drupal\Core\Messenger\LegacyMessenger;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Messenger
 * @coversDefaultClass \Drupal\Core\Messenger\LegacyMessenger
 *
 * Note: The Symphony PHPUnit Bridge automatically treats any test class that
 * starts with "Legacy" as a deprecation. To subvert that, reverse it here.
 *
 * @see http://symfony.com/blog/new-in-symfony-2-7-phpunit-bridge
 * @see https://www.drupal.org/node/2931598#comment-12395743
 */
class MessengerLegacyTest extends KernelTestBase {

  /**
   * Retrieves the Messenger service from LegacyMessenger.
   *
   * @param \Drupal\Core\Messenger\LegacyMessenger $legacy_messenger
   *   The legacy messenger.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface|null
   *   A messenger implementation.
   */
  protected function getMessengerService(LegacyMessenger $legacy_messenger) {
    $method = new \ReflectionMethod($legacy_messenger, 'getMessengerService');
    $method->setAccessible(TRUE);
    return $method->invoke($legacy_messenger);
  }

  /**
   * @covers \Drupal::messenger
   * @covers ::getMessengerService
   * @covers ::all
   * @covers ::addMessage
   * @covers ::addError
   * @covers ::addStatus
   * @covers ::addWarning
   */
  public function testMessages() {
    // Save the current container for later use.
    $container = \Drupal::getContainer();

    // Unset the container to mimic not having one.
    \Drupal::unsetContainer();

    /** @var \Drupal\Core\Messenger\LegacyMessenger $messenger */
    // Verify that the Messenger service doesn't exists.
    $messenger = \Drupal::messenger();
    $this->assertNull($this->getMessengerService($messenger));

    // Add messages.
    $messenger->addMessage('Foobar', 'custom');
    $messenger->addMessage('Foobar', 'custom', TRUE);
    $messenger->addError('Foo');
    $messenger->addError('Foo', TRUE);

    // Verify that retrieving another instance and adding more messages works.
    $messenger = \Drupal::messenger();
    $messenger->addStatus('Bar');
    $messenger->addStatus('Bar', TRUE);
    $messenger->addWarning('Fiz');
    $messenger->addWarning('Fiz', TRUE);

    // Restore the container.
    \Drupal::setContainer($container);

    // Verify that the Messenger service exists.
    $messenger = \Drupal::messenger();
    $this->assertInstanceOf(Messenger::class, $this->getMessengerService($messenger));

    // Add more messages.
    $messenger->addMessage('Platypus', 'custom');
    $messenger->addMessage('Platypus', 'custom', TRUE);
    $messenger->addError('Rhinoceros');
    $messenger->addError('Rhinoceros', TRUE);
    $messenger->addStatus('Giraffe');
    $messenger->addStatus('Giraffe', TRUE);
    $messenger->addWarning('Cheetah');
    $messenger->addWarning('Cheetah', TRUE);

    // Verify all messages added via LegacyMessenger are accounted for.
    $messages = $messenger->all();
    $this->assertContains('Foobar', $messages['custom']);
    $this->assertContains('Foo', $messages[MessengerInterface::TYPE_ERROR]);
    $this->assertContains('Bar', $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertContains('Fiz', $messages[MessengerInterface::TYPE_WARNING]);

    // Verify all messages added via Messenger service are accounted for.
    $this->assertContains('Platypus', $messages['custom']);
    $this->assertContains('Rhinoceros', $messages[MessengerInterface::TYPE_ERROR]);
    $this->assertContains('Giraffe', $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertContains('Cheetah', $messages[MessengerInterface::TYPE_WARNING]);

    // Verify repeat counts.
    $this->assertCount(4, $messages['custom']);
    $this->assertCount(4, $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertCount(4, $messages[MessengerInterface::TYPE_WARNING]);
    $this->assertCount(4, $messages[MessengerInterface::TYPE_ERROR]);

    // Test deleteByType().
    $this->assertCount(4, $messenger->deleteByType(MessengerInterface::TYPE_WARNING));
    $this->assertCount(0, $messenger->messagesByType(MessengerInterface::TYPE_WARNING));
    $this->assertCount(4, $messenger->messagesByType(MessengerInterface::TYPE_ERROR));
  }

}

<?php

namespace Drupal\KernelTests\Core\Messenger;

use Drupal\Core\Messenger\LegacyMessenger;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Messenger
 * @coversDefaultClass \Drupal\Core\Messenger\LegacyMessenger
 */
class MessengerTest extends KernelTestBase {

  /**
   * Retrieves the Messenger service from LegacyMessenger.
   *
   * @param \Drupal\Core\Messenger\LegacyMessenger $legacy_messenger
   *
   * @return \Drupal\Core\Messenger\MessengerInterface|null
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
    $messenger->addMessage('Foobar');
    $messenger->addError('Foo');

    // Verify that retrieving another instance and adding more messages works.
    $messenger = \Drupal::messenger();
    $messenger->addStatus('Bar');
    $messenger->addWarning('Fiz');

    // Restore the container.
    \Drupal::setContainer($container);

    // Verify that the Messenger service exists.
    $messenger = \Drupal::messenger();
    $this->assertInstanceOf(Messenger::class, $this->getMessengerService($messenger));

    // Add more messages.
    $messenger->addMessage('Platypus');
    $messenger->addError('Rhinoceros');
    $messenger->addStatus('Giraffe');
    $messenger->addWarning('Cheetah');

    // Verify that all the messages are present and accounted for.
    $messages = $messenger->all();
    $this->assertContains('Foobar', $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertContains('Foo', $messages[MessengerInterface::TYPE_ERROR]);
    $this->assertContains('Bar', $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertContains('Fiz', $messages[MessengerInterface::TYPE_WARNING]);
    $this->assertContains('Platypus', $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertContains('Rhinoceros', $messages[MessengerInterface::TYPE_ERROR]);
    $this->assertContains('Giraffe', $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertContains('Cheetah', $messages[MessengerInterface::TYPE_WARNING]);
  }

}

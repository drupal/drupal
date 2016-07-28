<?php

namespace Drupal\Tests\Core\Messenger;

use Drupal\Core\Messenger\StaticMessenger;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * @coversDefaultClass \Drupal\Core\Messenger\StaticMessenger
 * @group messenger
 */
class StaticMessengerTest extends \PHPUnit_Framework_TestCase {

  use RandomGeneratorTrait;

  /**
   * The messenger under test.
   *
   * @var \Drupal\Core\Messenger\StaticMessenger
   */
  protected $messenger;

  /**
   * The page caching kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pageCacheKillSwitch;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->pageCacheKillSwitch = $this->prophesize(KillSwitch::class);
  }

  /**
   * @covers ::addMessage
   * @covers ::getMessages
   * @covers ::getMessagesByType
   * @covers ::deleteMessages
   * @covers ::deleteMessagesByType
   */
  public function testMessenger() {
    $message_a = $this->randomMachineName();
    $type_a = $this->randomMachineName();
    $message_b = $this->randomMachineName();
    $type_b = $this->randomMachineName();

    $this->pageCacheKillSwitch->trigger()->shouldBeCalled();

    $this->messenger = new StaticMessenger($this->pageCacheKillSwitch->reveal());

    // Test that if there are no messages, the default is an empty array.
    $this->assertEquals($this->messenger->getMessages(), []);

    // Test that adding a message returns the messenger and that the message can
    // be retrieved.
    $this->assertSame($this->messenger->addMessage($message_a, $type_a), $this->messenger);
    $this->messenger->addMessage($message_a, $type_a);
    $this->messenger->addMessage($message_a, $type_a, TRUE);
    $this->messenger->addMessage($message_b, $type_b, TRUE);
    $this->assertEquals([
      $type_a => [$message_a, $message_a],
      $type_b => [$message_b],
    ], $this->messenger->getMessages());

    // Test deleting messages of a certain type.
    $this->assertEquals($this->messenger->deleteMessagesByType($type_a), $this->messenger);
    $this->assertEquals([
      $type_b => [$message_b],
    ], $this->messenger->getMessages());

    // Test deleting all messages.
    $this->assertEquals($this->messenger->deleteMessages(), $this->messenger);
    $this->assertEquals([], $this->messenger->getMessages());
  }

}

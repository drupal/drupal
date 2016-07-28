<?php

namespace Drupal\Tests\Core\Messenger;

use Drupal\Core\Messenger\SessionMessenger;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Messenger\SessionMessenger
 * @group messenger
 */
class SessionMessengerTest extends UnitTestCase {

  /**
   * A copy of any existing session data to restore after the test.
   *
   * @var array
   */
  protected $existingSession;

  /**
   * The messenger under test.
   *
   * @var \Drupal\Core\Messenger\SessionMessenger
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

    $this->existingSession = isset($_SESSION) ? $_SESSION : NULL;
    $_SESSION = [];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    if ($this->existingSession !== NULL) {
      $_SESSION = $this->existingSession;
    }
    else {
      unset($_SESSION);
    }
  }

  /**
   * @covers ::addMessage
   * @covers ::getMessages
   * @covers ::getMessagesByType
   * @covers ::deleteMessages
   * @covers ::deleteMessagesByType
   */
  public function testMessenger() {
    $this->pageCacheKillSwitch->trigger()->shouldBeCalled();

    $this->messenger = new SessionMessenger($this->pageCacheKillSwitch->reveal());

    $message_a = $this->randomMachineName();
    $type_a = $this->randomMachineName();
    $message_b = $this->randomMachineName();
    $type_b = $this->randomMachineName();

    // Test that if there are no messages, the default is an empty array.
    $this->assertEquals($this->messenger->getMessages(), []);

    // Test that adding a message returns the messenger and that the message can
    // be retrieved.
    $this->assertEquals($this->messenger->addMessage($message_a, $type_a), $this->messenger);
    $this->messenger->addMessage($message_a, $type_a);
    $this->messenger->addMessage($message_a, $type_a, TRUE);
    $this->messenger->addMessage($message_b, $type_b, TRUE);
    $this->assertEquals($this->messenger->getMessages(), [
      $type_a => [$message_a, $message_a],
      $type_b => [$message_b],
    ]);

    // Test deleting messages of a certain type.
    $this->assertEquals($this->messenger->deleteMessagesByType($type_a), $this->messenger);
    $this->assertEquals($this->messenger->getMessages(), [
      $type_b => [$message_b],
    ]);

    // Test deleting all messages.
    $this->assertEquals($this->messenger->deleteMessages(), $this->messenger);
    $this->assertEquals($this->messenger->getMessages(), []);
  }

}

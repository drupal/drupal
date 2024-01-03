<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\WriteSafeSessionHandler;

/**
 * Tests \Drupal\Core\Session\WriteSafeSessionHandler.
 *
 * @coversDefaultClass \Drupal\Core\Session\WriteSafeSessionHandler
 * @group Session
 */
class WriteSafeSessionHandlerTest extends UnitTestCase {

  /**
   * The wrapped session handler.
   *
   * @var \SessionHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $wrappedSessionHandler;

  /**
   * The write safe session handler.
   *
   * @var \Drupal\Core\Session\WriteSafeSessionHandler
   */
  protected $sessionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->wrappedSessionHandler = $this->createMock('SessionHandlerInterface');
    $this->sessionHandler = new WriteSafeSessionHandler($this->wrappedSessionHandler);
  }

  /**
   * Tests creating a WriteSafeSessionHandler with default arguments.
   *
   * @covers ::__construct
   * @covers ::isSessionWritable
   * @covers ::write
   */
  public function testConstructWriteSafeSessionHandlerDefaultArgs() {
    $session_id = 'some-id';
    $session_data = 'serialized-session-data';

    $this->assertTrue($this->sessionHandler->isSessionWritable());

    // Writing should be enabled, return value passed to the caller by default.
    $this->wrappedSessionHandler->expects($this->exactly(2))
      ->method('write')
      ->with($session_id, $session_data)
      ->willReturnOnConsecutiveCalls(TRUE, FALSE);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertTrue($result);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertFalse($result);
  }

  /**
   * Tests creating a WriteSafeSessionHandler with session writing disabled.
   *
   * @covers ::__construct
   * @covers ::isSessionWritable
   * @covers ::write
   */
  public function testConstructWriteSafeSessionHandlerDisableWriting() {
    $session_id = 'some-id';
    $session_data = 'serialized-session-data';

    // Disable writing upon construction.
    $this->sessionHandler = new WriteSafeSessionHandler($this->wrappedSessionHandler, FALSE);

    $this->assertFalse($this->sessionHandler->isSessionWritable());

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertTrue($result);
  }

  /**
   * Tests using setSessionWritable to enable/disable session writing.
   *
   * @covers ::setSessionWritable
   * @covers ::write
   */
  public function testSetSessionWritable() {
    $session_id = 'some-id';
    $session_data = 'serialized-session-data';

    $this->assertTrue($this->sessionHandler->isSessionWritable());

    // Disable writing after construction.
    $this->sessionHandler->setSessionWritable(FALSE);
    $this->assertFalse($this->sessionHandler->isSessionWritable());

    $this->sessionHandler = new WriteSafeSessionHandler($this->wrappedSessionHandler, FALSE);

    $this->assertFalse($this->sessionHandler->isSessionWritable());

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertTrue($result);

    // Enable writing again.
    $this->sessionHandler->setSessionWritable(TRUE);
    $this->assertTrue($this->sessionHandler->isSessionWritable());

    // Writing should be enabled, return value passed to the caller by default.
    $this->wrappedSessionHandler->expects($this->exactly(2))
      ->method('write')
      ->with($session_id, $session_data)
      ->willReturnOnConsecutiveCalls(TRUE, FALSE);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertTrue($result);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertFalse($result);
  }

  /**
   * Tests that other invocations are passed unmodified to the wrapped handler.
   *
   * @covers ::setSessionWritable
   * @covers ::open
   * @covers ::read
   * @covers ::close
   * @covers ::destroy
   * @covers ::gc
   * @dataProvider providerTestOtherMethods
   */
  public function testOtherMethods($method, $expected_result, $args) {
    $invocation = $this->wrappedSessionHandler->expects($this->exactly(2))
      ->method($method)
      ->willReturn($expected_result);

    // Set the parameter matcher.
    call_user_func_array([$invocation, 'with'], $args);

    // Test with writable session.
    $this->assertTrue($this->sessionHandler->isSessionWritable());
    $actual_result = call_user_func_array([$this->sessionHandler, $method], $args);
    $this->assertSame($expected_result, $actual_result);

    // Test with non-writable session.
    $this->sessionHandler->setSessionWritable(FALSE);
    $this->assertFalse($this->sessionHandler->isSessionWritable());
    $actual_result = call_user_func_array([$this->sessionHandler, $method], $args);
    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Provides test data for the other methods test.
   *
   * @return array
   *   Test data.
   */
  public function providerTestOtherMethods() {
    return [
      ['open', TRUE, ['/some/path', 'some-session-id']],
      ['read', 'some-session-data', ['a-session-id']],
      ['close', TRUE, []],
      ['destroy', TRUE, ['old-session-id']],
      ['gc', 0, [42]],
    ];
  }

}

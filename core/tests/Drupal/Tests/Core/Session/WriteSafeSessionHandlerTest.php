<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Session\WriteSafeSessionHandlerTest.
 */

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
   * @var \SessionHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $wrappedSessionHandler;

  /**
   * The write safe session handler.
   *
   * @var \Drupal\Core\Session\WriteSafeSessionHandler
   */
  protected $sessionHandler;

  protected function setUp() {
    $this->wrappedSessionHandler = $this->getMock('SessionHandlerInterface');
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

    $this->assertSame($this->sessionHandler->isSessionWritable(), TRUE);

    // Writing should be enabled, return value passed to the caller by default.
    $this->wrappedSessionHandler->expects($this->at(0))
      ->method('write')
      ->with($session_id, $session_data)
      ->will($this->returnValue(TRUE));

    $this->wrappedSessionHandler->expects($this->at(1))
      ->method('write')
      ->with($session_id, $session_data)
      ->will($this->returnValue(FALSE));

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertSame($result, TRUE);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertSame($result, FALSE);
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

    $this->assertSame($this->sessionHandler->isSessionWritable(), FALSE);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertSame($result, TRUE);
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

    $this->assertSame($this->sessionHandler->isSessionWritable(), TRUE);

    // Disable writing after construction.
    $this->sessionHandler->setSessionWritable(FALSE);
    $this->assertSame($this->sessionHandler->isSessionWritable(), FALSE);

    $this->sessionHandler = new WriteSafeSessionHandler($this->wrappedSessionHandler, FALSE);

    $this->assertSame($this->sessionHandler->isSessionWritable(), FALSE);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertSame($result, TRUE);

    // Enable writing again.
    $this->sessionHandler->setSessionWritable(TRUE);
    $this->assertSame($this->sessionHandler->isSessionWritable(), TRUE);

    // Writing should be enabled, return value passed to the caller by default.
    $this->wrappedSessionHandler->expects($this->at(0))
      ->method('write')
      ->with($session_id, $session_data)
      ->will($this->returnValue(TRUE));

    $this->wrappedSessionHandler->expects($this->at(1))
      ->method('write')
      ->with($session_id, $session_data)
      ->will($this->returnValue(FALSE));

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertSame($result, TRUE);

    $result = $this->sessionHandler->write($session_id, $session_data);
    $this->assertSame($result, FALSE);
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
      ->will($this->returnValue($expected_result));

    // Set the parameter matcher.
    call_user_func_array([$invocation, 'with'], $args);

    // Test with writable session.
    $this->assertSame($this->sessionHandler->isSessionWritable(), TRUE);
    $actual_result = call_user_func_array([$this->sessionHandler, $method], $args);
    $this->assertSame($expected_result, $actual_result);

    // Test with non-writable session.
    $this->sessionHandler->setSessionWritable(FALSE);
    $this->assertSame($this->sessionHandler->isSessionWritable(), FALSE);
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
      ['gc', TRUE, [42]],
    ];
  }
}

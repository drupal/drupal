<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\WriteSafeSessionHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\Core\Session\WriteSafeSessionHandler.
 */
#[CoversClass(WriteSafeSessionHandler::class)]
#[Group('Session')]
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
   * @legacy-covers ::__construct
   * @legacy-covers ::isSessionWritable
   * @legacy-covers ::write
   */
  public function testConstructWriteSafeSessionHandlerDefaultArgs(): void {
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
   * @legacy-covers ::__construct
   * @legacy-covers ::isSessionWritable
   * @legacy-covers ::write
   */
  public function testConstructWriteSafeSessionHandlerDisableWriting(): void {
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
   * @legacy-covers ::setSessionWritable
   * @legacy-covers ::write
   */
  public function testSetSessionWritable(): void {
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
   * @legacy-covers ::setSessionWritable
   * @legacy-covers ::open
   * @legacy-covers ::read
   * @legacy-covers ::close
   * @legacy-covers ::destroy
   * @legacy-covers ::gc
   */
  #[DataProvider('providerTestOtherMethods')]
  public function testOtherMethods($method, $expected_result, $args): void {
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
  public static function providerTestOtherMethods(): array {
    return [
      ['open', TRUE, ['/some/path', 'some-session-id']],
      ['read', 'some-session-data', ['a-session-id']],
      ['close', TRUE, []],
      ['destroy', TRUE, ['old-session-id']],
      ['gc', 0, [42]],
    ];
  }

}

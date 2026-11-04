<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\WriteSafeSessionHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests \Drupal\Core\Session\WriteSafeSessionHandler.
 */
#[CoversClass(WriteSafeSessionHandler::class)]
#[Group('Session')]
class WriteSafeSessionHandlerTest extends UnitTestCase {

  /**
   * The wrapped session handler.
   */
  protected \SessionHandlerInterface&Stub $wrappedSessionHandler;

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

    $this->wrappedSessionHandler = $this->createStub('SessionHandlerInterface');
    $this->sessionHandler = new WriteSafeSessionHandler($this->wrappedSessionHandler);
  }

  /**
   * Reinitializes the wrapped session handler as a mock object.
   */
  protected function setUpMockWrappedSessionHandler(): void {
    $this->wrappedSessionHandler = $this->createMock(\SessionHandlerInterface::class);
    $this->sessionHandler = new WriteSafeSessionHandler($this->wrappedSessionHandler);
  }

  /**
   * Tests creating a WriteSafeSessionHandler with default arguments.
   *
   * @see ::__construct
   * @see ::isSessionWritable
   * @see ::write
   */
  public function testConstructWriteSafeSessionHandlerDefaultArgs(): void {
    $this->setUpMockWrappedSessionHandler();
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
   * @see ::__construct
   * @see ::isSessionWritable
   * @see ::write
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
   * @see ::setSessionWritable
   * @see ::write
   */
  public function testSetSessionWritable(): void {
    $this->setUpMockWrappedSessionHandler();
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
   * @see ::setSessionWritable
   * @see ::open
   * @see ::read
   * @see ::close
   * @see ::destroy
   * @see ::gc
   */
  #[DataProvider('providerTestOtherMethods')]
  public function testOtherMethods(string $method, bool|string|int $expected_result, array $args): void {
    $this->setUpMockWrappedSessionHandler();

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

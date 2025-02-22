<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * @coversDefaultClass \Drupal\Core\Logger\LoggerChannel
 * @group Logger
 */
class LoggerChannelTest extends UnitTestCase {

  /**
   * Tests LoggerChannel::log().
   *
   * @param callable $expected
   *   An anonymous function to use with $this->callback() of the logger mock.
   *   The function should check the $context array for expected values.
   * @param bool $request
   *   Whether to pass a request to the channel under test.
   * @param bool $account
   *   Whether to pass an account to the channel under test.
   *
   * @dataProvider providerTestLog
   * @covers ::log
   * @covers ::setCurrentUser
   * @covers ::setRequestStack
   */
  public function testLog(callable $expected, bool $request = FALSE, bool $account = FALSE): void {
    $channel = new LoggerChannel('test');
    $message = $this->randomMachineName();
    $logger = $this->createMock('Psr\Log\LoggerInterface');
    $logger->expects($this->once())
      ->method('log')
      ->with($this->anything(), $message, $this->callback($expected));
    $channel->addLogger($logger);
    if ($request) {
      $request_mock = $this->getMockBuilder(Request::class)
        ->onlyMethods(['getClientIp'])
        ->getMock();
      $request_mock->expects($this->any())
        ->method('getClientIp')
        ->willReturn('127.0.0.1');
      $request_mock->headers = $this->createMock(HeaderBag::class);

      $requestStack = new RequestStack();
      $requestStack->push($request_mock);
      $channel->setRequestStack($requestStack);
    }
    if ($account) {
      $account_mock = $this->createMock(AccountInterface::class);
      $account_mock->expects($this->any())
        ->method('id')
        ->willReturn(1);

      $channel->setCurrentUser($account_mock);
    }
    $channel->log(rand(0, 7), $message);
  }

  /**
   * Tests LoggerChannel::log() recursion protection.
   *
   * @covers ::log
   */
  public function testLogRecursionProtection(): void {
    $channel = new LoggerChannel('test');
    $logger = $this->createMock('Psr\Log\LoggerInterface');
    $logger->expects($this->exactly(LoggerChannel::MAX_CALL_DEPTH))
      ->method('log');
    $channel->addLogger($logger);
    $channel->addLogger(new NaughtyRecursiveLogger($channel));
    $channel->log(rand(0, 7), $this->randomMachineName());
  }

  /**
   * Tests LoggerChannel::addLoggers().
   *
   * @covers ::addLogger
   * @covers ::sortLoggers
   */
  public function testSortLoggers(): void {
    $channel = new LoggerChannel($this->randomMachineName());
    $index_order = '';
    for ($i = 0; $i < 4; $i++) {
      $logger = $this->createMock('Psr\Log\LoggerInterface');
      $logger->expects($this->once())
        ->method('log')
        ->willReturnCallback(function () use ($i, &$index_order) {
          // Append the $i to the index order, so that we know the order that
          // loggers got called with.
          $index_order .= $i;
        });
      $channel->addLogger($logger, $i);
    }

    $channel->log(rand(0, 7), $this->randomMachineName());
    // Ensure that the logger added in the end fired first.
    $this->assertEquals('3210', $index_order);
  }

  /**
   * Tests that $context['ip'] is a string even when the request's IP is NULL.
   */
  public function testNullIp(): void {
    // Create a logger that will fail if $context['ip'] is not an empty string.
    $logger = $this->createMock(LoggerInterface::class);
    $expected = function ($context) {
      return $context['channel'] == 'test' && $context['ip'] === '';
    };
    $logger->expects($this->once())
      ->method('log')
      ->with($this->anything(), 'Test message', $this->callback($expected));

    // Set up a request stack that has a request that will return NULL when
    // ::getClientIp() is called.
    $requestStack = new RequestStack();
    $request_mock = $this->getMockBuilder(Request::class)
      ->onlyMethods(['getClientIp'])
      ->getMock();
    $request_mock->expects($this->any())
      ->method('getClientIp')
      ->willReturn(NULL);
    $requestStack->push($request_mock);

    // Set up the logger channel for testing.
    $channel = new LoggerChannel('test');
    $channel->addLogger($logger);
    $channel->setRequestStack($requestStack);

    // Perform the test.
    $channel->log(rand(0, 7), 'Test message');
  }

  /**
   * Data provider for self::testLog().
   */
  public static function providerTestLog(): \Generator {
    // No request or account.
    yield [
      function ($context) {
        return $context['channel'] == 'test' && empty($context['uid']) && $context['ip'] === '';
      },
      FALSE,
      FALSE,
    ];

    // With account but not request. Since the request is not available the
    // current user should not be used.
    yield [
      function ($context) {
        return $context['uid'] === 0 && $context['ip'] === '';
      },
      FALSE,
      TRUE,
    ];

    // With request but not account.
    yield [
      function ($context) {
        return $context['ip'] === '127.0.0.1' && empty($context['uid']);
      },
      TRUE,
      FALSE,
    ];

    // Both request and account.
    yield [
      function ($context) {
        return $context['ip'] === '127.0.0.1' && $context['uid'] === 1;
      },
      TRUE,
      TRUE,
    ];
  }

}

/**
 * Stub class for testing LoggerChannel.
 */
class NaughtyRecursiveLogger implements LoggerInterface {
  use LoggerTrait;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $channel;

  /**
   * The message to log.
   *
   * @var string|\Stringable
   */
  protected $message;

  public function __construct(LoggerChannel $channel) {
    $this->channel = $channel;
  }

  public function log($level, string|\Stringable $message, array $context = []): void {
    $this->channel->log(rand(0, 7), $message, $context);
  }

}

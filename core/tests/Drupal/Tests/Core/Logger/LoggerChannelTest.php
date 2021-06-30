<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Logger\LoggerChannelTest.
 */

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Will be passed to the channel under test if present.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Will be passed to the channel under test if present.
   *
   * @dataProvider providerTestLog
   * @covers ::log
   * @covers ::setCurrentUser
   * @covers ::setRequestStack
   */
  public function testLog(callable $expected, Request $request = NULL, AccountInterface $current_user = NULL) {
    $channel = new LoggerChannel('test');
    $message = $this->randomMachineName();
    $logger = $this->createMock('Psr\Log\LoggerInterface');
    $logger->expects($this->once())
      ->method('log')
      ->with($this->anything(), $message, $this->callback($expected));
    $channel->addLogger($logger);
    if ($request) {
      $requestStack = new RequestStack();
      $requestStack->push($request);
      $channel->setRequestStack($requestStack);
    }
    if ($current_user) {
      $channel->setCurrentUser($current_user);
    }
    $channel->log(rand(0, 7), $message);
  }

  /**
   * Tests LoggerChannel::log() recursion protection.
   *
   * @covers ::log
   */
  public function testLogRecursionProtection() {
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
  public function testSortLoggers() {
    $channel = new LoggerChannel($this->randomMachineName());
    $index_order = '';
    for ($i = 0; $i < 4; $i++) {
      $logger = $this->createMock('Psr\Log\LoggerInterface');
      $logger->expects($this->once())
        ->method('log')
        ->will($this->returnCallback(function () use ($i, &$index_order) {
          // Append the $i to the index order, so that we know the order that
          // loggers got called with.
          $index_order .= $i;
        }));
      $channel->addLogger($logger, $i);
    }

    $channel->log(rand(0, 7), $this->randomMachineName());
    // Ensure that the logger added in the end fired first.
    $this->assertEquals('3210', $index_order);
  }

  /**
   * Data provider for self::testLog().
   */
  public function providerTestLog() {
    $account_mock = $this->createMock('Drupal\Core\Session\AccountInterface');
    $account_mock->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $request_mock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->setMethods(['getClientIp'])
      ->getMock();
    $request_mock->expects($this->any())
      ->method('getClientIp')
      ->will($this->returnValue('127.0.0.1'));
    $request_mock->headers = $this->createMock('Symfony\Component\HttpFoundation\ParameterBag');

    // No request or account.
    $cases[] = [
      function ($context) {
        return $context['channel'] == 'test' && empty($context['uid']) && empty($context['ip']);
      },
    ];
    // With account but not request. Since the request is not available the
    // current user should not be used.
    $cases[] = [
      function ($context) {
        return $context['uid'] === 0 && empty($context['ip']);
      },
      NULL,
      $account_mock,
    ];
    // With request but not account.
    $cases[] = [
      function ($context) {
        return $context['ip'] === '127.0.0.1' && empty($context['uid']);
      },
      $request_mock,
    ];
    // Both request and account.
    $cases[] = [
      function ($context) {
        return $context['ip'] === '127.0.0.1' && $context['uid'] === 1;
      },
      $request_mock,
      $account_mock,
    ];
    return $cases;
  }

}

class NaughtyRecursiveLogger implements LoggerInterface {
  use LoggerTrait;

  protected $channel;
  protected $message;

  public function __construct(LoggerChannel $channel) {
    $this->channel = $channel;
  }

  public function log($level, $message, array $context = []) {
    $this->channel->log(rand(0, 7), $message, $context);
  }

}

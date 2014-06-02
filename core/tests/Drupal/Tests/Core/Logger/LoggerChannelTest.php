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

// @todo Remove once watchdog() is removed.
if (!defined('WATCHDOG_EMERGENCY')) {
  define('WATCHDOG_EMERGENCY', 0);
  define('WATCHDOG_ALERT', 1);
  define('WATCHDOG_CRITICAL', 2);
  define('WATCHDOG_WARNING', 4);
  define('WATCHDOG_INFO', 6);
  define('WATCHDOG_DEBUG', 7);
}
// WATCHDOG_NOTICE is also defined in FormValidatorTest, so check independently.
if (!defined('WATCHDOG_NOTICE')) {
  define('WATCHDOG_NOTICE', 5);
}
// WATCHDOG_ERROR is also defined in FormBuilderTest, so check independently.
if (!defined('WATCHDOG_ERROR')) {
  define('WATCHDOG_ERROR', 3);
}

/**
 * Tests the logger channel.
 *
 * @see \Drupal\Core\Logger\LoggerChannel
 * @coversDefaultClass \Drupal\Core\Logger\LoggerChannel
 *
 * @group Drupal
 * @group Logger
 */
class LoggerChannelTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Logger channel',
      'description' => 'Unit tests for the logger channel object.',
      'group' => 'Logger',
    );
  }

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
   * @covers ::setRequest
   */
  public function testLog(callable $expected, Request $request = NULL, AccountInterface $current_user = NULL) {
    $channel = new LoggerChannel('test');
    $message = $this->randomName();
    $logger = $this->getMock('Psr\Log\LoggerInterface');
    $logger->expects($this->once())
      ->method('log')
      ->with($this->anything(), $message, $this->callback($expected));
    $channel->addLogger($logger);
    if ($request) {
      $channel->setRequest($request);
    }
    if ($current_user) {
      $channel->setCurrentUser($current_user);
    }
    $channel->log(rand(0, 7), $message);
  }

  /**
   * Tests LoggerChannel::addLoggers().
   *
   * @covers ::addLogger
   * @covers ::sortLoggers
   */
  public function testSortLoggers() {
    $channel = new LoggerChannel($this->randomName());
    $index_order = '';
    for ($i = 0; $i < 4; $i++) {
      $logger = $this->getMock('Psr\Log\LoggerInterface');
      $logger->expects($this->once())
        ->method('log')
        ->will($this->returnCallback(function () use ($i, &$index_order) {
          // Append the $i to the index order, so that we know the order that
          // loggers got called with.
          $index_order .= $i;
        }));
      $channel->addLogger($logger, $i);
    }

    $channel->log(rand(0, 7), $this->randomName());
    // Ensure that the logger added in the end fired first.
    $this->assertEquals($index_order, '3210');
  }

  /**
   * Data provider for self::testLog().
   */
  public function providerTestLog() {
    $account_mock = $this->getMock('Drupal\Core\Session\AccountInterface');
    $account_mock->expects($this->exactly(2))
      ->method('id')
      ->will($this->returnValue(1));

    $request_mock = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $request_mock->expects($this->exactly(2))
      ->method('getClientIp')
      ->will($this->returnValue('127.0.0.1'));
    $request_mock->headers = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');

    // No request or account.
    $cases [] = array(
      function ($context) {
        return $context['channel'] == 'test' && empty($contex['uid']) && empty($context['ip']);
      },
    );
    // With account but not request.
    $cases [] = array(
      function ($context) {
        return $context['uid'] === 1 && empty($context['ip']);
      },
      NULL,
      $account_mock,
    );
    // With request but not account.
    $cases [] = array(
      function ($context) {
        return $context['ip'] === '127.0.0.1' && empty($contex['uid']);
      },
      $request_mock,
    );
    // Both request and account.
    $cases [] = array(
      function ($context) {
        return $context['ip'] === '127.0.0.1' && $context['uid'] === 1;
      },
      $request_mock,
      $account_mock,
    );
    return $cases;
  }

}

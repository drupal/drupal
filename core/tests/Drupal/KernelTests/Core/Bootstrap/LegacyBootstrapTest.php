<?php

namespace Drupal\KernelTests\Core\Bootstrap;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy bootstrap functions.
 *
 * @group Bootstrap
 * @group legacy
 */
class LegacyBootstrapTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests watchdog_exception() deprecation.
   */
  public function testWatchdogException(): void {
    $logger = new TestLogger();
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory */
    $loggerFactory = \Drupal::service('logger.factory');
    $loggerFactory->addLogger($logger);
    $this->expectDeprecation('watchdog_exception() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Utility\Error::logException() instead. See https://www.drupal.org/node/2932520');
    $e = new \RuntimeException("foo");
    watchdog_exception('test', $e);
    $this->assertTrue($logger->hasRecordThatContains(Error::DEFAULT_ERROR_MESSAGE, RfcLogLevel::ERROR));
  }

}

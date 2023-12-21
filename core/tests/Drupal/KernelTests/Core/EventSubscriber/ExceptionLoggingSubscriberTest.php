<?php

namespace Drupal\KernelTests\Core\EventSubscriber;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that HTTP exceptions are logged correctly.
 *
 * @group system
 */
class ExceptionLoggingSubscriberTest extends KernelTestBase {

  /**
   * The service name for a logger implementation that collects anything logged.
   *
   * @var string
   */
  protected $testLogServiceName = 'exception_logging_subscriber_test.logger';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'test_page_test'];

  /**
   * Tests \Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber::onException().
   */
  public function testExceptionLogging() {
    $http_kernel = \Drupal::service('http_kernel');

    $channel_map = [
      400 => 'client error',
      401 => 'client error',
      403 => 'access denied',
      404 => 'page not found',
      405 => 'client error',
      408 => 'client error',
      // Do not check the 500 status code here because it would be caught by
      // Drupal\Core\EventSubscriberExceptionTestSiteSubscriber which has lower
      // priority.
      501 => 'php',
      502 => 'php',
      503 => 'php',
    ];

    $level_map = [
      400 => RfcLogLevel::WARNING,
      401 => RfcLogLevel::WARNING,
      403 => RfcLogLevel::WARNING,
      404 => RfcLogLevel::WARNING,
      405 => RfcLogLevel::WARNING,
      408 => RfcLogLevel::WARNING,
      501 => RfcLogLevel::ERROR,
      502 => RfcLogLevel::ERROR,
      503 => RfcLogLevel::ERROR,
    ];

    // Ensure that noting is logged.
    $this->assertEmpty($this->container->get($this->testLogServiceName)->cleanLogs());

    // Temporarily disable error log as the ExceptionLoggingSubscriber logs 5xx
    // HTTP errors using error_log().
    $error_log = ini_set('error_log', '/dev/null');
    foreach ($channel_map as $code => $channel) {
      $request = Request::create('/test-http-response-exception/' . $code);
      $http_kernel->handle($request);
    }
    ini_set('error_log', $error_log);

    $expected_channels = array_values($channel_map);
    $expected_levels = array_values($level_map);

    $logs = $this->container->get($this->testLogServiceName)->cleanLogs();
    foreach ($expected_channels as $key => $expected_channel) {
      $this->assertEquals($expected_channel, $logs[$key][2]['channel']);
      $this->assertEquals($expected_levels[$key], $logs[$key][0]);

      // Verify that @backtrace_string is removed from client error.
      if ($logs[$key][2]['channel'] === 'client error') {
        $this->assertArrayNotHasKey('@backtrace_string', $logs[$key][2]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container
      ->register($this->testLogServiceName, BufferingLogger::class)
      ->addTag('logger');
  }

}

<?php

declare(strict_types=1);

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
   *
   * @dataProvider exceptionDataProvider
   */
  public function testExceptionLogging(int $error_code, string $channel, int $log_level, string $exception = ''): void {
    $http_kernel = \Drupal::service('http_kernel');

    // Ensure that noting is logged.
    $this->assertEmpty($this->container->get($this->testLogServiceName)->cleanLogs());

    // Temporarily disable error log as the ExceptionLoggingSubscriber logs 5xx
    // HTTP errors using error_log().
    $error_log = ini_set('error_log', '/dev/null');
    $request = Request::create('/test-http-response-exception/' . $error_code);

    if ($exception) {
      $this->expectException($exception);
    }
    $http_kernel->handle($request);
    ini_set('error_log', $error_log);

    $logs = $this->container->get($this->testLogServiceName)->cleanLogs();
    $this->assertEquals($channel, $logs[0][2]['channel']);
    $this->assertEquals($log_level, $logs[0][0]);

    // Verify that @backtrace_string is removed from client error.
    if ($logs[0][2]['channel'] === 'client error') {
      $this->assertArrayNotHasKey('@backtrace_string', $logs[0][2]);
    }
  }

  /**
   * Returns data for testing exception logging.
   */
  public static function exceptionDataProvider(): array {
    return [
      [400, 'client error', RfcLogLevel::WARNING],
      [401, 'client error', RfcLogLevel::WARNING],
      [403, 'access denied', RfcLogLevel::WARNING],
      [404, 'page not found', RfcLogLevel::WARNING],
      [405, 'client error', RfcLogLevel::WARNING],
      [408, 'client error', RfcLogLevel::WARNING],
      // Do not check the 500 status code here because it would be caught by
      // Drupal\Core\EventSubscriberExceptionTestSiteSubscriber which has lower
      // priority.
      [501, 'php', RfcLogLevel::ERROR],
      [502, 'php', RfcLogLevel::ERROR],
      [503, 'php', RfcLogLevel::ERROR],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container
      ->register($this->testLogServiceName, BufferingLogger::class)
      ->addTag('logger');
  }

}

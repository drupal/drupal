<?php

declare(strict_types = 1);

namespace Drupal\Tests;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides various methods to aid in collecting performance data during tests.
 *
 * @ingroup testing
 */
trait PerformanceTestTrait {

  /**
   * Helper for ::setUp().
   *
   * Resets configuration to be closer to production settings.
   *
   * @see \Drupal\Tests\BrowserTestBase::setUp()
   */
  private function doSetUpTasks(): void {
    \Drupal::configFactory()->getEditable('system.performance')
      ->set('css.preprocess', TRUE)
      ->set('js.preprocess', TRUE)
      ->save();
  }

  /**
   * Helper for ::installModulesFromClassProperty().
   *
   * To use this, override BrowserTestBase::installModulesFromClassProperty()
   * and call this helper.
   *
   * @see \Drupal\Tests\BrowserTestBase::installModulesFromClassProperty()
   */
  private function doInstallModulesFromClassProperty(ContainerInterface $container) {
    // Bypass everything that WebDriverTestBase does here to get closer to
    // a production configuration.
    BrowserTestBase::installModulesFromClassProperty($container);
  }

  /**
   * Helper for ::getMinkDriverArgs().
   *
   * To use this, override BrowserTestBase::getMinkDriverArgs() and call this
   * helper.
   *
   * @return string
   *   The JSON encoded driver args with performance logging preferences added.
   *
   * @see \Drupal\Tests\BrowserTestBase::getMinkDriverArgs()
   */
  private function doGetMinkDriverArgs(): string {
    // Add performance logging preferences to the existing driver arguments to
    // avoid clobbering anything set via environment variables.
    // @see https://chromedriver.chromium.org/logging/performance-log
    $parent_driver_args = parent::getMinkDriverArgs();
    $driver_args = json_decode($parent_driver_args, TRUE);

    $driver_args[1]['goog:loggingPrefs'] = [
      'browser' => 'ALL',
      'performance' => 'ALL',
      'performanceTimeline' => 'ALL',
    ];
    $driver_args[1]['chromeOptions']['perfLoggingPrefs'] = [
      'traceCategories' => 'timeline,devtools.timeline,browser',
    ];

    return json_encode($driver_args);
  }

  /**
   * Executes a callable and collects performance data.
   *
   * @param callable $callable
   *   A callable, for example ::drupalGet().
   * @param string|null $service_name
   *   An optional human readable identifier to enable sending traces to an Open
   *   Telemetry endpoint (if configured).
   *
   * @return \Drupal\Tests\PerformanceData
   *   A PerformanceData value object.
   */
  public function collectPerformanceData(callable $callable, ?string $service_name = NULL): PerformanceData {
    $session = $this->getSession();
    $session->getDriver()->getWebDriverSession()->log('performance');
    $return = $callable();
    $performance_data = $this->processChromeDriverPerformanceLogs($service_name);
    if (isset($return)) {
      $performance_data->setReturnValue($performance_data);
    }

    return $performance_data;
  }

  /**
   * Gets the chromedriver performance log and extracts metrics from it.
   *
   * The performance log is cumulative, and is emptied each time it is
   * collected. If the log grows to the point it will overflow, it may also be
   * emptied resulting in lost messages. There is no specific
   * LargestContentfulPaint event, instead there are
   * largestContentfulPaint::Candidate events which may be superseded by later
   * events. From manual testing none of the core pages result in more than
   * two largestContentfulPaint::Candidate events, so we keep looking until
   * either two have been sent, or until 30 seconds has passed.
   *
   * @todo https://www.drupal.org/project/drupal/issues/3379757
   *
   * @param string|null $service_name
   *   An optional human readable identifier so that traces can be grouped together.
   *
   * @return \Drupal\Tests\PerformanceData
   *   An instance of the performance data value object.
   */
  protected function processChromeDriverPerformanceLogs(?string $service_name): PerformanceData {
    $attempts = 0;
    $lcp_count = 0;
    $request_count = 0;
    $response_count = 0;
    $messages = [];
    $session = $this->getSession();
    while ($attempts <= 30) {
      $attempts++;
      $performance_log = $session->getDriver()->getWebDriverSession()->log('performance');

      foreach ($performance_log as $entry) {
        $decoded = json_decode($entry['message'], TRUE);
        $message = $decoded['message'];
        if ($message['method'] === 'Tracing.dataCollected' && $message['params']['name'] === 'largestContentfulPaint::Candidate') {
          $lcp_count++;
        }
        if ($message['method'] === 'Network.requestWillBeSent') {
          $request_count++;
        }
        if ($message['method'] === 'Network.responseReceived') {
          $response_count++;
        }
        $messages[] = $message;
      }
      // Performance entries are logged indeterminately since page loading
      // varies by request. Chrome returns a response as soon as the HTML page
      // has returned to the browser, but CSS, JavaScript, image and AJAX
      // requests may all occur after this, and in turn trigger further requests
      // and page rendering events, and there is no performance log event for
      // the page loading 'finishing' since this is cannot be detected as such.
      // Therefore, continue collecting performance data until all of the
      // following are true, or until 30 seconds has passed:
      // - a largestContentfulPaint::candidate event has been fired
      // - all network requests have received a response
      // - no new performance log events have been recorded since the last
      //   iteration.
      if ($lcp_count && empty($performance_log) && ($request_count === $response_count)) {
        break;
      }
      sleep(1);
    }
    $performance_data = new PerformanceData();
    $this->collectNetworkData($messages, $performance_data);

    if (isset($service_name)) {
      $this->openTelemetryTracing($messages, $service_name);
    }

    return $performance_data;
  }

  /**
   * Prepares data for assertions.
   *
   * @param array $messages
   *   The chromedriver performance log messages.
   * @param \Drupal\Tests\PerformanceData $performance_data
   *   An instance of the performance data value object.
   */
  private function collectNetworkData(array $messages, PerformanceData $performance_data): void {
    $stylesheet_count = 0;
    $script_count = 0;
    foreach ($messages as $message) {
      if ($message['method'] === 'Network.responseReceived') {
        if ($message['params']['type'] === 'Stylesheet') {
          $stylesheet_count++;
        }
        if ($message['params']['type'] === 'Script') {
          $script_count++;
        }
      }
    }
    $performance_data->setStylesheetCount($stylesheet_count);
    $performance_data->setScriptCount($script_count);
  }

  /**
   * Sends metrics to OpenTelemetry.
   *
   * @param array $messages
   *   The ChromeDriver performance log messages.
   * @param string $service_name
   *   A human readable identifier so that traces can be grouped together.
   *
   * @see https://opentelemetry.io/docs/instrumentation/php/manual/
   */
  private function openTelemetryTracing(array $messages, string $service_name): void {
    // Open telemetry timestamps are always in nanoseconds.
    // @todo: consider moving these to trait constants once we require PHP 8.2.
    $nanoseconds_per_second = 1000_000_000;
    $nanoseconds_per_millisecond = 1000_000;
    $nanoseconds_per_microsecond = 1000;

    $collector = $_ENV['OTEL_COLLECTOR'] ?? NULL;
    if ($collector === NULL) {
      return;
    }
    $timestamp = NULL;
    $url = NULL;
    $dom_loaded_timestamp_page = NULL;
    $dom_loaded_timestamp_timeline = NULL;
    $timestamp_since_os_boot = NULL;
    foreach ($messages as $message) {
      // Since chrome timestamps are since OS start, we take the first network
      // request as '0' and calculate offsets against that.
      if ($timestamp === NULL && $message['method'] === 'Network.requestWillBeSent') {
        $url = $message['params']['request']['url'];
        $timestamp = (int) ($message['params']['wallTime'] * $nanoseconds_per_second);
        // Network timestamps are formatted as a second float with three point
        // precision. Record this so it can be compared against other
        // timestamps.
        $timestamp_since_os_boot = (int) ($message['params']['timestamp'] * $nanoseconds_per_second);
      }
      // The DOM content loaded event is in both the 'page' and 'timeline'
      // sections of the performance log in different formats. This lets us
      // compare 'ts' and 'timestamp' which are not only in two different
      // formats, but appear to start from slightly different points in time.
      // By subtracting one from the other, we can generate an offset to apply
      // to all other 'ts' timestamps. Note that if the two events actually
      // happen at different times, then the offset will be wrong by that
      // difference.
      // See https://bugs.chromium.org/p/chromium/issues/detail?id=1463436
      if ($dom_loaded_timestamp_page === NULL && $message['method'] === 'Page.domContentEventFired') {
        $dom_loaded_timestamp_page = $message['params']['timestamp'] * $nanoseconds_per_second;
      }
      if ($dom_loaded_timestamp_timeline === NULL && $message['method'] === 'Tracing.dataCollected' && isset($message['params']['args']['data']['type']) && $message['params']['args']['data']['type'] === 'DOMContentLoaded') {
        $dom_loaded_timestamp_timeline = $message['params']['ts'] * $nanoseconds_per_microsecond;
      }
    }

    $offset = $dom_loaded_timestamp_page - $dom_loaded_timestamp_timeline;
    $entry = $this->getSession()->evaluateScript("window.performance.getEntriesByType('navigation')")[0];
    $first_request_timestamp = $entry['requestStart'] * $nanoseconds_per_millisecond;
    $first_response_timestamp = $entry['responseStart'] * $nanoseconds_per_millisecond;

    // @todo: get commit hash from an environment variable and add this as an
    // additional attribute.
    // @see https://www.drupal.org/project/drupal/issues/3379761
    $resource = ResourceInfoFactory::defaultResource();
    $resource = $resource->merge(ResourceInfo::create(Attributes::create([
      ResourceAttributes::SERVICE_NAMESPACE => 'Drupal',
      ResourceAttributes::SERVICE_NAME => $service_name,
      ResourceAttributes::SERVICE_INSTANCE_ID => 1,
      ResourceAttributes::SERVICE_VERSION => \Drupal::VERSION,
      ResourceAttributes::DEPLOYMENT_ENVIRONMENT => 'local',
    ])));

    $transport = (new OtlpHttpTransportFactory())->create($collector, 'application/x-protobuf');
    $exporter = new SpanExporter($transport);
    $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter), NULL, $resource);
    $tracer = $tracerProvider->getTracer('Drupal');

    $span = $tracer->spanBuilder('main')
      ->setStartTimestamp($timestamp)
      ->setAttribute('http.method', 'GET')
      ->setAttribute('http.url', $url)
      ->setSpanKind(SpanKind::KIND_SERVER)
      ->startSpan();
    $last_timestamp = $first_byte_timestamp = (int) ($timestamp + ($first_response_timestamp - $first_request_timestamp));

    try {
      $scope = $span->activate();
      $first_byte_span = $tracer->spanBuilder('firstByte')
        ->setStartTimestamp($timestamp)
        ->setAttribute('http.url', $url)
        ->startSpan();
      $first_byte_span->end($first_byte_timestamp);
      // Largest contentful paint is not available from
      // window.performance::getEntriesByType() so use the performance log
      // messages to get it instead.
      $lcp_timestamp = NULL;
      $fcp_timestamp = NULL;
      $lcp_size = 0;
      foreach ($messages as $message) {
        if ($message['method'] === 'Tracing.dataCollected' && $message['params']['name'] === 'firstContentfulPaint') {
          if (!isset($fcp_timestamp)) {
            // Tracing timestamps are microseconds since OS boot. However they
            // appear to start from a slightly different point from page
            // timestamps. Apply an offset calculated from DOM content loaded.
            // See https://bugs.chromium.org/p/chromium/issues/detail?id=1463436
            $fcp_timestamp = ($message['params']['ts'] * $nanoseconds_per_microsecond) + $offset;
            $fcp_span = $tracer->spanBuilder('firstContentfulPaint')
              ->setStartTimestamp($timestamp)
              ->setAttribute('http.url', $url)
              ->startSpan();
            $last_timestamp = $first_contentful_paint_timestamp = (int) ($timestamp + ($fcp_timestamp - $timestamp_since_os_boot));
            $fcp_span->end($first_contentful_paint_timestamp);
          }
        }

        // There can be multiple largestContentfulPaint candidates, remember
        // the largest one.
        if ($message['method'] === 'Tracing.dataCollected' && $message['params']['name'] === 'largestContentfulPaint::Candidate' && $message['params']['args']['data']['size'] > $lcp_size) {
          $lcp_timestamp = ($message['params']['ts'] * $nanoseconds_per_microsecond) + $offset;
          $lcp_size = $message['params']['args']['data']['size'];
        }
      }
      if (isset($lcp_timestamp)) {
        $lcp_span = $tracer->spanBuilder('largestContentfulPaint')
          ->setStartTimestamp($timestamp)
          ->setAttribute('http.url', $url)
          ->startSpan();
        $last_timestamp = $largest_contentful_paint_timestamp = (int) ($timestamp + ($lcp_timestamp - $timestamp_since_os_boot));
        $lcp_span->setAttribute('lcp.size', $lcp_size);
        $lcp_span->end($largest_contentful_paint_timestamp);
      }
    }
    finally {
      // The scope must be detached before the span is ended, because it's
      // created from the span.
      if (isset($scope)) {
        $scope->detach();
      }
      $span->end($last_timestamp);
      $tracerProvider->shutdown();
    }
  }

}

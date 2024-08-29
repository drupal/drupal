<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Core\Database\Event\DatabaseEvent;
use Drupal\performance_test\Cache\CacheTagOperation;
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
    // Support legacy key.
    $chrome_options_key = isset($driver_args[1]['chromeOptions']) ? 'chromeOptions' : 'goog:chromeOptions';
    $driver_args[1][$chrome_options_key]['perfLoggingPrefs'] = [
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
    // Clear all existing performance logs before collecting new data. This is
    // necessary because responses are returned back to tests prior to image
    // and asset responses are returning to the browser, and before
    // post-response tasks are guaranteed to have run. Assume that if there is
    // no performance data logged by the child request within one second, that
    // this means everything has finished.
    $collection = \Drupal::keyValue('performance_test');
    while ($collection->get('performance_test_data')) {
      $collection->deleteAll();
      sleep(1);
    }

    $session = $this->getSession();
    $session->getDriver()->getWebDriverSession()->log('performance');
    $collection->deleteAll();
    $return = $callable();
    $performance_data = $this->processChromeDriverPerformanceLogs($service_name);
    if (isset($return)) {
      $performance_data->setReturnValue($return);
    }

    $performance_test_data = $collection->get('performance_test_data');
    if ($performance_test_data) {
      // This property is set by \Drupal\Core\Test\TestSetupTrait and is needed.
      if (!isset($this->databasePrefix)) {
        throw new \Exception('Cannot log queries without knowing the database prefix.');
      }

      // Separate queries into two buckets, one for queries from the cache
      // backend, and one for everything else (including those for cache tags).
      $cache_get_count = 0;
      $cache_set_count = 0;
      $cache_delete_count = 0;
      $cache_tag_is_valid_count = 0;
      $cache_tag_invalidation_count = 0;
      $cache_tag_checksum_count = 0;
      foreach ($performance_test_data['database_events'] as $event) {
        // Don't log queries from the database cache backend because they're
        // logged separately as cache operations.
        if (!static::isDatabaseCache($event)) {
          // Make the query easier to read and log it.
          static::logQuery(
            $performance_data,
            str_replace([$this->databasePrefix, "\r\n", "\r", "\n"], ['', ' ', ' ', ' '], $event->queryString),
            $event->args
          );
        }
      }
      foreach ($performance_test_data['cache_operations'] as $operation) {
        if (in_array($operation['operation'], ['get', 'getMultiple'], TRUE)) {
          $cache_get_count++;
        }
        elseif (in_array($operation['operation'], ['set', 'setMultiple'], TRUE)) {
          $cache_set_count++;
        }
        elseif (in_array($operation['operation'], ['delete', 'deleteMultiple'], TRUE)) {
          $cache_delete_count++;
        }
      }
      foreach ($performance_test_data['cache_tag_operations'] as $operation) {
        match($operation['operation']) {
          CacheTagOperation::GetCurrentChecksum => $cache_tag_checksum_count++,
          CacheTagOperation::IsValid => $cache_tag_is_valid_count++,
          CacheTagOperation::InvalidateTags => $cache_tag_invalidation_count++,
        };
      }
      $performance_data->setCacheGetCount($cache_get_count);
      $performance_data->setCacheSetCount($cache_set_count);
      $performance_data->setCacheDeleteCount($cache_delete_count);
      $performance_data->setCacheTagChecksumCount($cache_tag_checksum_count);
      $performance_data->setCacheTagIsValidCount($cache_tag_is_valid_count);
      $performance_data->setCacheTagInvalidationCount($cache_tag_invalidation_count);
    }

    return $performance_data;
  }

  /**
   * Logs a query in the performance data.
   *
   * @param \Drupal\Tests\PerformanceData $performance_data
   *   The performance data object to log the query on.
   * @param string $query
   *   The raw query.
   * @param array $args
   *   The query arguments.
   */
  protected static function logQuery(PerformanceData $performance_data, string $query, array $args): void {
    // Make queries with random variables invariable.
    if (str_starts_with($query, 'INSERT INTO "semaphore"')) {
      $args[':db_insert_placeholder_1'] = 'LOCK_ID';
      $args[':db_insert_placeholder_2'] = 'EXPIRE';
    }
    elseif (str_starts_with($query, 'DELETE FROM "semaphore"')) {
      $args[':db_condition_placeholder_1'] = 'LOCK_ID';
    }
    elseif (str_starts_with($query, 'SELECT "base_table"."uid" AS "uid", "base_table"."uid" AS "base_table_uid" FROM "users"')) {
      $args[':db_condition_placeholder_0'] = 'ACCOUNT_NAME';
    }
    elseif (str_starts_with($query, 'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f"')) {
      $args[':db_condition_placeholder_1'] = 'CLIENT_IP';
      $args[':db_condition_placeholder_2'] = 'TIMESTAMP';
    }
    elseif (str_starts_with($query, 'UPDATE "users_field_data" SET "login"')) {
      $args[':db_update_placeholder_0'] = 'TIMESTAMP';
    }
    elseif (str_starts_with($query, 'INSERT INTO "sessions"')) {
      $args[':db_insert_placeholder_0'] = 'SESSION_ID';
      $args[':db_insert_placeholder_2'] = 'CLIENT_IP';
      $args[':db_insert_placeholder_3'] = 'SESSION_DATA';
      $args[':db_insert_placeholder_4'] = 'TIMESTAMP';
    }
    elseif (str_starts_with($query, 'SELECT "session" FROM "sessions"')) {
      $args[':sid'] = 'SESSION_ID';
    }
    elseif (str_starts_with($query, 'SELECT 1 AS "expression" FROM "sessions"')) {
      $args[':db_condition_placeholder_0'] = 'SESSION_ID';
    }
    elseif (str_starts_with($query, 'DELETE FROM "sessions"')) {
      $args[':db_condition_placeholder_0'] = 'TIMESTAMP';
    }
    elseif (str_starts_with($query, 'INSERT INTO "watchdog"')) {
      $args[':db_insert_placeholder_3'] = 'WATCHDOG_DATA';
      $args[':db_insert_placeholder_6'] = 'LOCATION';
      $args[':db_insert_placeholder_7'] = 'REFERER';
      $args[':db_insert_placeholder_8'] = 'CLIENT_IP';
      $args[':db_insert_placeholder_9'] = 'TIMESTAMP';
    }
    elseif (str_starts_with($query, 'SELECT "name", "route", "fit" FROM "router"')) {
      if (preg_match('@/sites/simpletest/(\d{8})/files/css/(.*)@', $args[':patterns__0'], $matches)) {
        $search = [$matches[1], $matches[2]];
        $replace = ['TEST_ID', 'CSS_FILE'];
        foreach ($args as $name => $arg) {
          if (!is_string($arg)) {
            continue;
          }
          $args[$name] = str_replace($search, $replace, $arg);
        }
      }
    }
    elseif (str_starts_with($query, 'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table"')) {
      if (str_contains($args[':db_condition_placeholder_1'], 'files/css')) {
        $args[':db_condition_placeholder_1'] = 'CSS_FILE';
      }
    }
    elseif (str_starts_with($query, 'SELECT "name", "value" FROM "key_value_expire" WHERE "expire" >')) {
      $args[':now'] = 'NOW';
      $args[':keys__0'] = 'KEY';
    }

    // Inline query arguments and log the query.
    $query = str_replace(array_keys($args), array_values(static::quoteQueryArgs($args)), $query);
    $performance_data->logQuery($query);
  }

  /**
   * Wraps query arguments in double quotes if they're a string.
   *
   * @param array $args
   *   The raw query arguments.
   *
   * @return array
   *   The conditionally quoted query arguments.
   */
  protected static function quoteQueryArgs(array $args): array {
    $conditionalQuote = function ($arg) {
      return is_int($arg) || is_float($arg) ? $arg : '"' . $arg . '"';
    };
    return array_map($conditionalQuote, $args);
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
    $stylesheet_bytes = 0;
    $script_bytes = 0;
    $stylesheet_urls = [];
    $script_urls = [];
    // Collect the CSS and JavaScript responses from the network log build an
    // associative array so that if multiple page or AJAX requests have
    // requested styles and scripts, only unique files will be counted.
    foreach ($messages as $message) {
      if ($message['method'] === 'Network.responseReceived') {
        if ($message['params']['type'] === 'Stylesheet') {
          $url = $message['params']['response']['url'];
          $stylesheet_urls[$url] = $url;

        }
        if ($message['params']['type'] === 'Script') {
          $url = $message['params']['response']['url'];
          $script_urls[$url] = $url;
        }
      }
    }
    // Get the actual files from disk when calculating filesize, to ensure
    // consistency between testing environments. The performance log has
    // 'encodedDataLength' for network requests, however in the case that the
    // file has already been requested by the browser, this will be the length
    // of a HEAD response for 304 not modified or similar. Additionally, core's
    // aggregation adds the basepath to CSS aggregates, resulting in slightly
    // different file sizes depending on whether tests run in a subdirectory or
    // not.
    foreach ($stylesheet_urls as $url) {
      $stylesheet_count++;
      if ($GLOBALS['base_path'] === '/') {
        $filename = ltrim(parse_url($url, PHP_URL_PATH), '/');
        $stylesheet_bytes += strlen(file_get_contents($filename));
      }
      else {
        $filename = str_replace($GLOBALS['base_path'], '', parse_url($url, PHP_URL_PATH));
        // Strip the basepath from the contents of the file so that tests
        // running in a subdirectory get the same results.
        $stylesheet_bytes += strlen(str_replace($GLOBALS['base_path'], '/', file_get_contents($filename)));
      }
    }
    foreach ($script_urls as $url) {
      $script_count++;
      if ($GLOBALS['base_path'] === '/') {
        $filename = ltrim(parse_url($url, PHP_URL_PATH), '/');
      }
      else {
        $filename = str_replace($GLOBALS['base_path'], '', parse_url($url, PHP_URL_PATH));
      }
      $script_bytes += strlen(file_get_contents($filename));
    }

    $performance_data->setStylesheetCount($stylesheet_count);
    $performance_data->setStylesheetBytes($stylesheet_bytes);
    $performance_data->setScriptCount($script_count);
    $performance_data->setScriptBytes($script_bytes);
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
    // @todo Consider moving these to trait constants once we require PHP 8.2.
    $nanoseconds_per_second = 1000_000_000;
    $nanoseconds_per_millisecond = 1000_000;
    $nanoseconds_per_microsecond = 1000;

    $collector = getenv('OTEL_COLLECTOR');
    if (!$collector) {
      return;
    }
    $first_request_timestamp = NULL;
    $first_response_timestamp = NULL;
    $request_wall_time = NULL;
    $response_wall_time = NULL;
    $url = NULL;
    foreach ($messages as $message) {
      // Since chrome timestamps are since OS start, we take the first network
      // request and response, determine the wall times of each, then calculate
      // offsets from those for everything else.
      if ($message['method'] === 'Tracing.dataCollected'
        && isset($message['params']['name'])
        && $message['params']['name'] === 'ResourceReceiveResponse') {
        $first_response_timestamp = (int) ($message['params']['ts'] * $nanoseconds_per_microsecond);

        // Get the actual timestamp of the response which is a millisecond unix
        // epoch timestamp. The log doesn't provide this for the request.
        $response_wall_time = (int) ($message['params']['args']['data']['responseTime'] * $nanoseconds_per_millisecond);

        // 'requestTime' is in the format 'seconds since OS boot with
        // microsecond precision'.
        $first_request_timestamp = (int) ($message['params']['args']['data']['timing']['requestTime'] * $nanoseconds_per_second);
        // By subtracting the request timestamp from the response wall time we
        // get the request wall time.
        $request_wall_time = ($response_wall_time - ($first_response_timestamp - $first_request_timestamp));
        break;
      }
    }
    if ($first_response_timestamp === NULL) {
      // If the $first_response_timestamp is null, this means we got an
      // incomplete log from chromedriver, mark the test as skipped.
      $this->markTestSkipped('Incomplete log from chromedriver, giving up.');
    }

    // @todo Get commit hash from an environment variable and add this as an
    //   additional attribute.
    //   @see https://www.drupal.org/project/drupal/issues/3379761
    $resource = ResourceInfoFactory::defaultResource();
    $resource = $resource->merge(ResourceInfo::create(Attributes::create([
      ResourceAttributes::SERVICE_NAMESPACE => 'Drupal',
      ResourceAttributes::SERVICE_NAME => $service_name,
      ResourceAttributes::SERVICE_INSTANCE_ID => 1,
      ResourceAttributes::SERVICE_VERSION => \Drupal::VERSION,
      ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'local',
    ])));

    $otel_collector_headers = getenv('OTEL_COLLECTOR_HEADERS') ?: [];
    if ($otel_collector_headers) {
      $otel_collector_headers = json_decode($otel_collector_headers, TRUE);
    }
    $transport = (new OtlpHttpTransportFactory())->create($collector, 'application/x-protobuf', $otel_collector_headers);
    $exporter = new SpanExporter($transport);
    $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter), NULL, $resource);
    $tracer = $tracerProvider->getTracer('Drupal');

    $span = $tracer->spanBuilder('main')
      ->setStartTimestamp($request_wall_time)
      ->setAttribute('http.method', 'GET')
      ->setAttribute('http.url', $url)
      ->setSpanKind(SpanKind::KIND_SERVER)
      ->startSpan();

    $last_timestamp = $response_wall_time;

    try {
      $scope = $span->activate();
      $first_byte_span = $tracer->spanBuilder('firstByte')
        ->setStartTimestamp($request_wall_time)
        ->setAttribute('http.url', $url)
        ->startSpan();
      $first_byte_span->end($response_wall_time);

      $collection = \Drupal::keyValue('performance_test');
      $performance_test_data = $collection->get('performance_test_data');
      $query_events = $performance_test_data['database_events'] ?? [];
      foreach ($query_events as $key => $event) {
        if (static::isDatabaseCache($event)) {
          continue;
        }
        // Use the first part of the database query for the span name.
        $query_span = $tracer->spanBuilder(substr($event->queryString, 0, 64))
          ->setStartTimestamp((int) ($event->startTime * $nanoseconds_per_second))
          ->setAttribute('query.string', $event->queryString)
          ->setAttribute('query.args', var_export($event->args, TRUE))
          ->setAttribute('query.caller', var_export($event->caller, TRUE))
          ->startSpan();
        $query_span->end((int) ($event->time * $nanoseconds_per_second));
      }
      $cache_operations = $performance_test_data['cache_operations'] ?? [];
      foreach ($cache_operations as $operation) {
        $cache_span = $tracer->spanBuilder('cache ' . $operation['operation'] . ' ' . $operation['bin'])
          ->setStartTimestamp((int) ($operation['start'] * $nanoseconds_per_second))
          ->setAttribute('cache.operation', $operation['operation'])
          ->setAttribute('cache.cids', $operation['cids'])
          ->setAttribute('cache.bin', $operation['bin'])
          ->startSpan();
        $cache_span->end((int) ($operation['stop'] * $nanoseconds_per_second));
      }
      $cache_tag_operations = $performance_test_data['cache_tag_operations'] ?? [];
      foreach ($cache_tag_operations as $operation) {
        $cache_tag_span = $tracer->spanBuilder('cache_tag ' . $operation['operation']->name . ' ' . $operation['tags'])
          ->setStartTimestamp((int) ($operation['start'] * $nanoseconds_per_second))
          ->setAttribute('cache_tag.operation', $operation['operation']->name)
          ->setAttribute('cache_tag.tags', $operation['tags'])
          ->startSpan();
        $cache_tag_span->end((int) ($operation['stop'] * $nanoseconds_per_second));
      }

      $lcp_timestamp = NULL;
      $fcp_timestamp = NULL;
      $lcp_size = 0;
      foreach ($messages as $message) {
        if ($message['method'] === 'Tracing.dataCollected' && $message['params']['name'] === 'firstContentfulPaint') {
          if (!isset($fcp_timestamp)) {
            // Tracing timestamps are microseconds since OS boot.
            $fcp_timestamp = $message['params']['ts'] * $nanoseconds_per_microsecond;
            $fcp_span = $tracer->spanBuilder('firstContentfulPaint')
              ->setStartTimestamp($request_wall_time)
              ->setAttribute('http.url', $url)
              ->startSpan();
            $last_timestamp = $first_contentful_paint_wall_time = (int) ($request_wall_time + ($fcp_timestamp - $first_request_timestamp));
            $fcp_span->end($first_contentful_paint_wall_time);
          }
        }

        // There can be multiple largestContentfulPaint candidates, remember
        // the largest one.
        if ($message['method'] === 'Tracing.dataCollected' && $message['params']['name'] === 'largestContentfulPaint::Candidate' && $message['params']['args']['data']['size'] > $lcp_size) {
          $lcp_timestamp = $message['params']['ts'] * $nanoseconds_per_microsecond;
          $lcp_size = $message['params']['args']['data']['size'];
        }
      }
      if (isset($lcp_timestamp)) {
        $lcp_span = $tracer->spanBuilder('largestContentfulPaint')
          ->setStartTimestamp($request_wall_time)
          ->setAttribute('http.url', $url)
          ->startSpan();
        $last_timestamp = $largest_contentful_paint_wall_time = (int) ($request_wall_time + ($lcp_timestamp - $first_request_timestamp));
        $lcp_span->setAttribute('lcp.size', $lcp_size);
        $lcp_span->end($largest_contentful_paint_wall_time);
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

  /**
   * Asserts that a count is between a min and max inclusively.
   *
   * @param int $min
   *   Minimum value.
   * @param int $max
   *   Maximum value.
   * @param int $actual
   *   The number to assert against.
   *
   * @return void
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   */
  protected function assertCountBetween(int $min, int $max, int $actual) {
    static::assertThat(
      $actual,
      static::logicalAnd(static::greaterThanOrEqual($min), static::lessThanOrEqual($max)),
      "$actual is greater or equal to $min and is smaller or equal to $max",
    );
  }

  /**
   * Checks whether a database event is from the database cache implementation.
   *
   * @param Drupal\Core\Database\Event\DatabaseEvent $event
   *   The database event.
   *
   * @return bool
   *   Whether the event was triggered by the database cache implementation.
   */
  protected static function isDatabaseCache(DatabaseEvent $event): bool {
    $class = str_replace('\\\\', '\\', $event->caller['class']);
    return is_a($class, '\Drupal\Core\Cache\DatabaseBackend', TRUE) || is_a($class, '\Drupal\Core\Cache\DatabaseCacheTagsChecksum', TRUE);
  }

}

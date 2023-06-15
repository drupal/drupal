<?php

declare(strict_types = 1);

namespace Drupal\FunctionalJavascriptTests;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects performance metrics.
 *
 * @ingroup testing
 */
class PerformanceTestBase extends WebDriverTestBase {

  /**
   * The number of stylesheets requested.
   */
  protected int $stylesheetCount = 0;

  /**
   * The number of scripts requested.
   */
  protected int $scriptCount = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::configFactory()->getEditable('system.performance')
      ->set('css.preprocess', TRUE)
      ->set('js.preprocess', TRUE)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function installModulesFromClassProperty(ContainerInterface $container) {
    // Bypass everything that WebDriverTestBase does here to get closer to
    // a production configuration.
    BrowserTestBase::installModulesFromClassProperty($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMinkDriverArgs() {

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
      'traceCategories' => 'devtools.timeline',
      'enableNetwork' => TRUE,
    ];

    return json_encode($driver_args);
  }

  /**
   * {@inheritdoc}
   */
  public function drupalGet($path, array $options = [], array $headers = []): string {
    // Reset the performance log from any previous HTTP requests. The log is
    // cumulative until it is collected explicitly.
    $session = $this->getSession();
    $session->getDriver()->getWebDriverSession()->log('performance');
    $return = parent::drupalGet($path, $options, $headers);
    $this->getChromeDriverPerformanceMetrics($path);
    return $return;
  }

  /**
   * Gets the chromedriver performance log and extracts metrics from it.
   */
  protected function getChromeDriverPerformanceMetrics(string|Url $path): void {
    $session = $this->getSession();
    $performance_log = $session->getDriver()->getWebDriverSession()->log('performance');

    $messages = [];
    foreach ($performance_log as $entry) {
      $decoded = json_decode($entry['message'], TRUE);
      $messages[] = $decoded['message'];
    }
    $this->collectNetworkData($path, $messages);
  }

  /**
   * Prepares data for assertions.
   *
   * @param string|\Drupal\Core\Url $path
   *   The path as passed to static::drupalGet().
   * @param array $messages
   *   The chromedriver performance log messages.
   */
  protected function collectNetworkData(string|Url $path, array $messages): void {
    $this->stylesheetCount = 0;
    $this->scriptCount = 0;
    foreach ($messages as $message) {
      if ($message['method'] === 'Network.responseReceived') {
        if ($message['params']['type'] === 'Stylesheet') {
          $this->stylesheetCount++;
        }
        if ($message['params']['type'] === 'Script') {
          $this->scriptCount++;
        }
      }
    }
  }

}

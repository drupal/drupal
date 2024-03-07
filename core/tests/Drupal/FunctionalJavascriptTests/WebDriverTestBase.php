<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Exception\DriverException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Runner\BaseTestRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs a browser test using a driver that supports JavaScript.
 *
 * Base class for testing browser interaction implemented in JavaScript.
 *
 * @ingroup testing
 */
abstract class WebDriverTestBase extends BrowserTestBase {

  /**
   * Determines if a test should fail on JavaScript console errors.
   *
   * @var bool
   */
  protected $failOnJavascriptConsoleErrors = TRUE;

  /**
   * Disables CSS animations in tests for more reliable testing.
   *
   * CSS animations are disabled by installing the css_disable_transitions_test
   * module. Set to FALSE to test CSS animations.
   *
   * @var bool
   */
  protected $disableCssAnimations = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  protected function initMink() {
    if (!is_a($this->minkDefaultDriverClass, DrupalSelenium2Driver::class, TRUE)) {
      throw new \UnexpectedValueException(sprintf("%s has to be an instance of %s", $this->minkDefaultDriverClass, DrupalSelenium2Driver::class));
    }
    $this->minkDefaultDriverArgs = ['chrome', ['goog:chromeOptions' => ['w3c' => FALSE]], 'http://localhost:4444'];

    try {
      return parent::initMink();
    }
    catch (DriverException $e) {
      if ($this->minkDefaultDriverClass === DrupalSelenium2Driver::class) {
        $this->markTestSkipped("The test wasn't able to connect to your webdriver instance. For more information read core/tests/README.md.\n\nThe original message while starting Mink: {$e->getMessage()}");
      }
      else {
        throw $e;
      }
    }
    catch (\Exception $e) {
      $this->markTestSkipped('An unexpected error occurred while starting Mink: ' . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function installModulesFromClassProperty(ContainerInterface $container) {
    self::$modules = [
      'js_testing_ajax_request_test',
      'js_testing_log_test',
      'jquery_keyevent_polyfill_test',
    ];
    if ($this->disableCssAnimations) {
      self::$modules[] = 'css_disable_transitions_test';
    }
    parent::installModulesFromClassProperty($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage() {
    parent::initFrontPage();
    // Set a standard window size so that all javascript tests start with the
    // same viewport.
    $this->getSession()->resizeWindow(1024, 768);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->mink) {
      $status = $this->getStatus();
      if ($status === BaseTestRunner::STATUS_ERROR || $status === BaseTestRunner::STATUS_WARNING || $status === BaseTestRunner::STATUS_FAILURE) {
        // Ensure we capture the output at point of failure.
        @$this->htmlOutput();
      }
      // Wait for all requests to finish. It is possible that an AJAX request is
      // still on-going.
      $result = $this->getSession()->wait(5000, 'window.drupalActiveXhrCount === 0 || typeof window.drupalActiveXhrCount === "undefined"');
      if (!$result) {
        // If the wait is unsuccessful, there may still be an AJAX request in
        // progress. If we tear down now, then this AJAX request may fail with
        // missing database tables, because tear down will have removed them.
        // Rather than allow it to fail, throw an explicit exception now
        // explaining what the problem is.
        throw new \RuntimeException('Unfinished AJAX requests while tearing down a test');
      }

      $warnings = $this->getSession()->evaluateScript("JSON.parse(sessionStorage.getItem('js_testing_log_test.warnings') || JSON.stringify([]))");
      foreach ($warnings as $warning) {
        if (str_starts_with($warning, '[Deprecation]')) {
          // phpcs:ignore Drupal.Semantics.FunctionTriggerError
          @trigger_error('Javascript Deprecation:' . substr($warning, 13), E_USER_DEPRECATED);
        }
      }
    }
    parent::tearDown();
  }

  /**
   * Triggers a test failure if a JavaScript error was encountered.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *
   * @postCondition
   */
  protected function failOnJavaScriptErrors(): void {
    if ($this->failOnJavascriptConsoleErrors) {
      $errors = $this->getSession()->evaluateScript("JSON.parse(sessionStorage.getItem('js_testing_log_test.errors') || JSON.stringify([]))");
      if (!empty($errors)) {
        $this->fail(implode("\n", $errors));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getMinkDriverArgs() {
    if ($this->minkDefaultDriverClass === DrupalSelenium2Driver::class) {
      $json = getenv('MINK_DRIVER_ARGS_WEBDRIVER') ?: parent::getMinkDriverArgs();
      if (!($json === FALSE || $json === '')) {
        $args = json_decode($json, TRUE);
        if (isset($args[1]['chromeOptions'])) {
          @trigger_error('The "chromeOptions" array key is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use "goog:chromeOptions instead. See https://www.drupal.org/node/3422624', E_USER_DEPRECATED);
          $args[1]['goog:chromeOptions'] = $args[1]['chromeOptions'];
          unset($args[1]['chromeOptions']);
        }
        if (isset($args[0]) && $args[0] === 'chrome' && !isset($args[1]['goog:chromeOptions']['w3c'])) {
          // @todo https://www.drupal.org/project/drupal/issues/3421202
          //   Deprecate defaulting behavior and require w3c to be set.
          $args[1]['goog:chromeOptions']['w3c'] = FALSE;
        }
        $json = json_encode($args);
      }
      return $json;
    }
    return parent::getMinkDriverArgs();
  }

  /**
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * @param string $condition
   *   JS condition to wait until it becomes TRUE.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) A message to display with the assertion. If left blank, a
   *   default message will be displayed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *
   * @see \Behat\Mink\Driver\DriverInterface::evaluateScript()
   */
  protected function assertJsCondition($condition, $timeout = 10000, $message = '') {
    $message = $message ?: "JavaScript condition met:\n" . $condition;
    $result = $this->getSession()->getDriver()->wait($timeout, $condition);
    $this->assertTrue($result, $message);
  }

  /**
   * Creates a screenshot.
   *
   * @param string $filename
   *   The file name of the resulting screenshot including a writable path. For
   *   example, /tmp/test_screenshot.jpg.
   * @param bool $set_background_color
   *   (optional) By default this method will set the background color to white.
   *   Set to FALSE to override this behavior.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   * @throws \Behat\Mink\Exception\DriverException
   *   When the operation cannot be done.
   */
  protected function createScreenshot($filename, $set_background_color = TRUE) {
    $session = $this->getSession();
    if ($set_background_color) {
      $session->executeScript("document.body.style.backgroundColor = 'white';");
    }
    $image = $session->getScreenshot();
    file_put_contents($filename, $image);
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new WebDriverWebAssert($this->getSession($name), $this->baseUrl);
  }

  /**
   * Gets the current Drupal javascript settings and parses into an array.
   *
   * Unlike BrowserTestBase::getDrupalSettings(), this implementation reads the
   * current values of drupalSettings, capturing all changes made via javascript
   * after the page was loaded.
   *
   * @return array
   *   The Drupal javascript settings array.
   *
   * @see \Drupal\Tests\BrowserTestBase::getDrupalSettings()
   */
  protected function getDrupalSettings() {
    $script = <<<EndOfScript
(function () {
  if (typeof drupalSettings !== 'undefined') {
    return drupalSettings;
  }
})();
EndOfScript;
    $settings = $this->getSession()->evaluateScript($script) ?: [];
    if (isset($settings['ajaxPageState'])) {
      $settings['ajaxPageState']['libraries'] = UrlHelper::uncompressQueryParameter($settings['ajaxPageState']['libraries']);
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHtmlOutputHeaders() {
    // The webdriver API does not support fetching headers.
    return '';
  }

}

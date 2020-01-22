<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Exception\DriverException;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs a browser test using a driver that supports Javascript.
 *
 * Base class for testing browser interaction implemented in JavaScript.
 *
 * @ingroup testing
 */
abstract class WebDriverTestBase extends BrowserTestBase {

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
    $this->minkDefaultDriverArgs = ['chrome', NULL, 'http://localhost:4444'];

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
    self::$modules = ['js_deprecation_log_test'];
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
  protected function tearDown() {
    if ($this->mink) {
      // Wait for all requests to finish. It is possible that an AJAX request is
      // still on-going.
      $result = $this->getSession()->wait(5000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
      if (!$result) {
        // If the wait is unsuccessful, there may still be an AJAX request in
        // progress. If we tear down now, then this AJAX request may fail with
        // missing database tables, because tear down will have removed them.
        // Rather than allow it to fail, throw an explicit exception now
        // explaining what the problem is.
        throw new \RuntimeException('Unfinished AJAX requests while tearing down a test');
      }

      $warnings = $this->getSession()->evaluateScript("JSON.parse(sessionStorage.getItem('js_deprecation_log_test.warnings') || JSON.stringify([]))");
      foreach ($warnings as $warning) {
        if (strpos($warning, '[Deprecation]') === 0) {
          @trigger_error('Javascript Deprecation:' . substr($warning, 13), E_USER_DEPRECATED);
        }
      }
    }
    parent::tearDown();
  }

  /**
    * {@inheritdoc}
    */
  protected function getMinkDriverArgs() {
    if ($this->minkDefaultDriverClass === DrupalSelenium2Driver::class) {
      return getenv('MINK_DRIVER_ARGS_WEBDRIVER') ?: parent::getMinkDriverArgs();
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
    $message = $message ?: "Javascript condition met:\n" . $condition;
    $result = $this->getSession()->getDriver()->wait($timeout, $condition);
    $this->assertTrue($result, $message);
  }

  /**
   * Creates a screenshot.
   *
   * @param string $filename
   *   The file name of the resulting screenshot including a writeable path. For
   *   example, /tmp/test_screenshot.jpg.
   * @param bool $set_background_color
   *   (optional) By default this method will set the background color to white.
   *   Set to FALSE to override this behaviour.
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

    return $this->getSession()->evaluateScript($script) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getHtmlOutputHeaders() {
    // The webdriver API does not support fetching headers.
    return '';
  }

}

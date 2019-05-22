<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Exception\DriverException;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zumba\GastonJS\Exception\DeadClient;
use Zumba\Mink\Driver\PhantomJSDriver;

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
   *
   * To use a legacy phantomjs based approach, please use PhantomJSDriver::class.
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  protected function initMink() {
    if ($this->minkDefaultDriverClass === DrupalSelenium2Driver::class) {
      $this->minkDefaultDriverArgs = ['chrome', NULL, 'http://localhost:4444'];
    }
    elseif ($this->minkDefaultDriverClass === PhantomJSDriver::class) {
      // Set up the template cache used by the PhantomJS mink driver.
      $path = $this->tempFilesDirectory . DIRECTORY_SEPARATOR . 'browsertestbase-templatecache';
      $this->minkDefaultDriverArgs = [
        'http://127.0.0.1:8510',
        $path,
      ];
      if (!file_exists($path)) {
        mkdir($path);
      }
    }

    try {
      return parent::initMink();
    }
    catch (DeadClient $e) {
      $this->markTestSkipped('PhantomJS is either not installed or not running. Start it via phantomjs --ssl-protocol=any --ignore-ssl-errors=true vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1024 768&');
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
    if ($this->disableCssAnimations) {
      self::$modules = ['css_disable_transitions_test'];
    }
    parent::installModulesFromClassProperty($container);
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
    }
    parent::tearDown();
  }

  /**
    * {@inheritdoc}
    */
  protected function getMinkDriverArgs() {
    if ($this->minkDefaultDriverClass === DrupalSelenium2Driver::class) {
      return getenv('MINK_DRIVER_ARGS_WEBDRIVER') ?: getenv('MINK_DRIVER_ARGS_PHANTOMJS') ?: parent::getMinkDriverArgs();
    }
    elseif ($this->minkDefaultDriverClass === PhantomJSDriver::class) {
      return getenv('MINK_DRIVER_ARGS_PHANTOMJS') ?: parent::getMinkDriverArgs();
    }
    return parent::getMinkDriverArgs();
  }

  /**
   * Asserts that the element with the given CSS selector is visible.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   * @param string $message
   *   Optional message to show alongside the assertion.
   *
   * @deprecated in Drupal 8.1.0, will be removed before Drupal 9.0.0. Use
   *   \Behat\Mink\Element\NodeElement::isVisible() instead.
   */
  protected function assertElementVisible($css_selector, $message = '') {
    $this->assertTrue($this->getSession()->getDriver()->isVisible($this->cssSelectToXpath($css_selector)), $message);
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 8.1.0 and will be removed in 9.0.0. Use \Behat\Mink\Element\NodeElement::isVisible() instead.', E_USER_DEPRECATED);
  }

  /**
   * Asserts that the element with the given CSS selector is not visible.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   * @param string $message
   *   Optional message to show alongside the assertion.
   *
   * @deprecated in Drupal 8.1.0, will be removed before Drupal 9.0.0. Use
   *   \Behat\Mink\Element\NodeElement::isVisible() instead.
   */
  protected function assertElementNotVisible($css_selector, $message = '') {
    $this->assertFalse($this->getSession()->getDriver()->isVisible($this->cssSelectToXpath($css_selector)), $message);
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 8.1.0 and will be removed in 9.0.0. Use \Behat\Mink\Element\NodeElement::isVisible() instead.', E_USER_DEPRECATED);
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
   * @throws \PHPUnit_Framework_AssertionFailedError
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
   *   The file name of the resulting screenshot. If using the default phantomjs
   *   driver then this should be a JPG filename.
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

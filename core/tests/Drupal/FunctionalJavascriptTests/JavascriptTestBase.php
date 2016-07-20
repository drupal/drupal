<?php

namespace Drupal\FunctionalJavascriptTests;

use Drupal\Tests\BrowserTestBase;
use Zend\Escaper\Escaper;
use Zumba\Mink\Driver\PhantomJSDriver;

/**
 * Runs a browser test using PhantomJS.
 *
 * Base class for testing browser interaction implemented in JavaScript.
 */
abstract class JavascriptTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = PhantomJSDriver::class;

  /**
   * {@inheritdoc}
   */
  protected function initMink() {
    // Set up the template cache used by the PhantomJS mink driver.
    $path = $this->tempFilesDirectory . DIRECTORY_SEPARATOR . 'browsertestbase-templatecache';
    $this->minkDefaultDriverArgs = [
      'http://127.0.0.1:8510',
      $path,
    ];
    if (!file_exists($path)) {
      mkdir($path);
    }
    return parent::initMink();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Wait for all requests to finish. It is possible that an AJAX request is
    // still on-going.
    $result = $this->getSession()->wait(5000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$result) {
      // If the wait is unsuccessful, there may still be an AJAX request in
      // progress. If we tear down now, then this AJAX request may fail with
      // missing database tables, because tear down will have removed them. Rather
      // than allow it to fail, throw an explicit exception now explaining what
      // the problem is.
      throw new \RuntimeException('Unfinished AJAX requests whilst tearing down a test');
    }
    parent::tearDown();
  }

  /**
   * Asserts that the element with the given CSS selector is visible.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   * @param string $message
   *   Optional message to show alongside the assertion.
   *
   * @deprecated in Drupal 8.1.x, will be removed before Drupal 8.3.x. Use
   *   \Behat\Mink\Element\NodeElement::isVisible() instead.
   */
  protected function assertElementVisible($css_selector, $message = '') {
    $this->assertTrue($this->getSession()->getDriver()->isVisible($this->cssSelectToXpath($css_selector)), $message);
  }

  /**
   * Asserts that the element with the given CSS selector is not visible.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   * @param string $message
   *   Optional message to show alongside the assertion.
   *
   * @deprecated in Drupal 8.1.x, will be removed before Drupal 8.3.x. Use
   *   \Behat\Mink\Element\NodeElement::isVisible() instead.
   */
  protected function assertElementNotVisible($css_selector, $message = '') {
    $this->assertFalse($this->getSession()->getDriver()->isVisible($this->cssSelectToXpath($css_selector)), $message);
  }

  /**
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * @param string $condition
   *   JS condition to wait until it becomes TRUE.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (optional) A message to display with the assertion. If left blank, a
   *   default message will be displayed.
   *
   * @throws \PHPUnit_Framework_AssertionFailedError
   *
   * @see \Behat\Mink\Driver\DriverInterface::evaluateScript()
   */
  protected function assertJsCondition($condition, $timeout = 1000, $message = '') {
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
   * @param string $background_color
   *   (optional) Background color name. To use the default background color set
   *   to NULL, however this can result in completely black screenshots if the
   *   theme does not have a background color. This string is escaped by
   *   \Zend\Escaper\Escaper::escapeJs().
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   * @throws \Behat\Mink\Exception\DriverException
   *   When the operation cannot be done.
   */
  protected function createScreenshot($filename, $background_color = 'white') {
    $session = $this->getSession();
    if (!empty($background_color)) {
      $escaper = new Escaper();
      $session->executeScript("document.body.style.backgroundColor = '" . $escaper->escapeJs($background_color) . "';");
    }
    $image = $session->getScreenshot();
    file_put_contents($filename, $image);
  }

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    return new JSWebAssert($this->getSession($name), $this->baseUrl);
  }

}

<?php

namespace Drupal\FunctionalJavascriptTests;

use Drupal\Tests\BrowserTestBase;
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

}

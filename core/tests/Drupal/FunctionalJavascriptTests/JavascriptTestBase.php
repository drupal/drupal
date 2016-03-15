<?php

/**
 * @file
 * Contains \Drupal\FunctionalJavascriptTests\JavascriptTestBase.
 */

namespace Drupal\FunctionalJavascriptTests;

use Drupal\simpletest\BrowserTestBase;
use Symfony\Component\CssSelector\CssSelector;
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
    parent::initMink();
  }

  /**
   * Asserts that the element with the given CSS selector is visible.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   * @param string $message
   *   Optional message to show alongside the assertion.
   */
  protected function assertElementVisible($css_selector, $message = '') {
    $this->assertTrue($this->getSession()->getDriver()->isVisible(CssSelector::toXPath($css_selector)), $message);
  }

  /**
   * Asserts that the element with the given CSS selector is not visible.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   * @param string $message
   *   Optional message to show alongside the assertion.
   */
  protected function assertElementNotVisible($css_selector, $message = '') {
    $this->assertFalse($this->getSession()->getDriver()->isVisible(CssSelector::toXPath($css_selector)), $message);
  }

  /**
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * @param int $timeout
   *   Timeout in milliseconds.
   * @param string|bool $condition
   *   JS condition, or FALSE to wait for the full duration of the timeout.
   *
   * @return bool
   *   The result of the JS condition.
   */
  protected function wait($timeout, $condition = FALSE) {
    return $this->getSession()->getDriver()->wait($timeout, $condition);
  }

}

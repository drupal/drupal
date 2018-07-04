<?php

namespace Drupal\FunctionalJavascriptTests;

@trigger_error('The ' . __NAMESPACE__ . '\JavascriptTestBase is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\WebDriverTestBase. See https://www.drupal.org/node/2945059', E_USER_DEPRECATED);

use Zumba\Mink\Driver\PhantomJSDriver;

/**
 * Runs a browser test using PhantomJS.
 *
 * Base class for testing browser interaction implemented in JavaScript.
 *
 * @deprecated in Drupal 8.6.x, will be removed before Drupal 9.0.0.
 * Use \Drupal\FunctionalJavascriptTests\WebDriverTestBase instead
 *
 * @see https://www.drupal.org/node/2945059
 */
abstract class JavascriptTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = PhantomJSDriver::class;

  /**
   * {@inheritdoc}
   */
  public function assertSession($name = NULL) {
    // Return a WebAssert that supports status code and header assertions.
    return new JSWebAssert($this->getSession($name), $this->baseUrl);
  }

}

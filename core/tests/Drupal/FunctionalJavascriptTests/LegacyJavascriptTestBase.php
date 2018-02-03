<?php

namespace Drupal\FunctionalJavascriptTests;

use Zumba\Mink\Driver\PhantomJSDriver;

/**
 * Runs a browser test using PhantomJS.
 *
 * Base class for testing browser interaction implemented in JavaScript.
 */
abstract class LegacyJavascriptTestBase extends JavascriptTestBase {

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

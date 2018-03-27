<?php

namespace Drupal\FunctionalJavascriptTests;

<<<<<<< HEAD
use Zumba\Mink\Driver\PhantomJSDriver;

=======
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
/**
 * Runs a browser test using PhantomJS.
 *
 * Base class for testing browser interaction implemented in JavaScript.
 */
abstract class LegacyJavascriptTestBase extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  protected $minkDefaultDriverClass = PhantomJSDriver::class;

  /**
   * {@inheritdoc}
   */
=======
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
  public function assertSession($name = NULL) {
    // Return a WebAssert that supports status code and header assertions.
    return new JSWebAssert($this->getSession($name), $this->baseUrl);
  }

}

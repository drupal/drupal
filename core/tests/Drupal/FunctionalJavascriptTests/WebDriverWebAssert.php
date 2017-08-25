<?php

namespace Drupal\FunctionalJavascriptTests;

/**
 * Defines a JSWebAssert with no support for status code and header assertions.
 */
class WebDriverWebAssert extends JSWebAssert {

  /**
   * The use of statusCodeEquals() is not available.
   *
   * @param int $code
   *   The status code.
   */
  public function statusCodeEquals($code) {
    @trigger_error('Support for statusCodeEquals is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::statusCodeEquals($code);
  }

  /**
   * The use of statusCodeNotEquals() is not available.
   *
   * @param int $code
   *   The status code.
   */
  public function statusCodeNotEquals($code) {
    @trigger_error('Support for statusCodeNotEquals is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::statusCodeNotEquals($code);
  }

  /**
   * The use of responseHeaderEquals() is not available.
   *
   * @param string $name
   *   The name of the header.
   * @param string $value
   *   The value to check the header against.
   */
  public function responseHeaderEquals($name, $value) {
    @trigger_error('Support for responseHeaderEquals is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::responseHeaderEquals($name, $value);
  }

  /**
   * The use of responseHeaderNotEquals() is not available.
   *
   * @param string $name
   *   The name of the header.
   * @param string $value
   *   The value to check the header against.
   */
  public function responseHeaderNotEquals($name, $value) {
    @trigger_error('Support for responseHeaderNotEquals is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::responseHeaderNotEquals($name, $value);
  }

}

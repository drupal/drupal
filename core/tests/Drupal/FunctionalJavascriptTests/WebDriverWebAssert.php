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

  /**
   * The use of responseHeaderContains() is not available.
   *
   * @param string $name
   *   The name of the header.
   * @param string $value
   *   The value to check the header against.
   */
  public function responseHeaderContains($name, $value) {
    @trigger_error('Support for responseHeaderContains is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::responseHeaderContains($name, $value);
  }

  /**
   * The use of responseHeaderNotContains() is not available.
   *
   * @param string $name
   *   The name of the header.
   * @param string $value
   *   The value to check the header against.
   */
  public function responseHeaderNotContains($name, $value) {
    @trigger_error('Support for responseHeaderNotContains is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::responseHeaderNotContains($name, $value);
  }

  /**
   * The use of responseHeaderMatches() is not available.
   *
   * @param string $name
   *   The name of the header.
   * @param string $regex
   *   The value to check the header against.
   */
  public function responseHeaderMatches($name, $regex) {
    @trigger_error('Support for responseHeaderMatches is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::responseHeaderMatches($name, $regex);
  }

  /**
   * The use of responseHeaderNotMatches() is not available.
   *
   * @param string $name
   *   The name of the header.
   * @param string $regex
   *   The value to check the header against.
   */
  public function responseHeaderNotMatches($name, $regex) {
    @trigger_error('Support for responseHeaderNotMatches is to be dropped from Javascript tests. See https://www.drupal.org/node/2857562.');
    parent::responseHeaderNotMatches($name, $regex);
  }

}

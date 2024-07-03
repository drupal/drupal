<?php

declare(strict_types=1);

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
    @trigger_error('WebDriverWebAssert::statusCodeEquals() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
    parent::statusCodeEquals($code);
  }

  /**
   * The use of statusCodeNotEquals() is not available.
   *
   * @param int $code
   *   The status code.
   */
  public function statusCodeNotEquals($code) {
    @trigger_error('WebDriverWebAssert::statusCodeNotEquals() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
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
    @trigger_error('WebDriverWebAssert::responseHeaderEquals() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
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
    @trigger_error('WebDriverWebAssert::responseHeaderNotEquals() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
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
    @trigger_error('WebDriverWebAssert::responseHeaderContains() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
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
    @trigger_error('WebDriverWebAssert::responseHeaderNotContains() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
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
    @trigger_error('WebDriverWebAssert::responseHeaderMatches() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
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
    @trigger_error('WebDriverWebAssert::responseHeaderNotMatches() is deprecated in drupal:8.4.0 and is removed in drupal:12.0.0. See https://www.drupal.org/node/2857562', E_USER_DEPRECATED);
    parent::responseHeaderNotMatches($name, $regex);
  }

}

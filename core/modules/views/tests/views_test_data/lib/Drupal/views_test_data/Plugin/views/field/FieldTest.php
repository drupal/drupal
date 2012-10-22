<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\field\FieldTest.
 */

namespace Drupal\views_test_data\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * @Plugin(
 *   id = "test_field",
 *   title = @Translation("Test field plugin"),
 *   help = @Translation("Provides a generic field test plugin.")
 * )
 */
class FieldTest extends FieldPluginBase {


  /**
   * A temporary stored test value for the test.
   *
   * @var string
   */
  protected $testValue;

  /**
   * Sets the testValue property.
   *
   * @param string $value
   *   The test value to set.
   */
  public function setTestValue($value) {
    $this->testValue = $value;
  }

  /**
   * Returns the testValue property.
   *
   * @return string
   */
  public function getTestValue() {
    return $this->testValue;
  }

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::add_self_tokens().
   */
  function add_self_tokens(&$tokens, $item) {
    $tokens['[test-token]'] = $this->getTestValue();
  }

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::render().
   */
  function render($values) {
    return $this->sanitizeValue($this->getTestValue());
  }

  /**
   * A mock function which allows to call placeholder from public.
   *
   * @return string
   *   The result of the placeholder method.
   */
  public function getPlaceholder() {
    return $this->placeholder();
  }

}

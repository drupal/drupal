<?php

namespace Drupal\views_test_data\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * @ViewsField("test_field")
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
   * {@inheritdoc}
   */
  protected function addSelfTokens(&$tokens, $item) {
    $tokens['{{ test_token }}'] = $this->getTestValue();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
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

<?php

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides methods to manage form state values.
 *
 * @see \Drupal\Core\Form\FormStateInterface
 *
 * @ingroup form_api
 */
trait FormStateValuesTrait {

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::getValues()
   */
  abstract public function &getValues();

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::getValue()
   */
  public function &getValue($key, $default = NULL) {
    $exists = NULL;
    $value = &NestedArray::getValue($this->getValues(), (array) $key, $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::setValues()
   */
  public function setValues(array $values) {
    $existing_values = &$this->getValues();
    $existing_values = $values;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::setValue()
   */
  public function setValue($key, $value) {
    NestedArray::setValue($this->getValues(), (array) $key, $value, TRUE);
    return $this;
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::unsetValue()
   */
  public function unsetValue($key) {
    NestedArray::unsetValue($this->getValues(), (array) $key);
    return $this;
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::hasValue()
   */
  public function hasValue($key) {
    $exists = NULL;
    $value = NestedArray::getValue($this->getValues(), (array) $key, $exists);
    return $exists && isset($value);
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::isValueEmpty()
   */
  public function isValueEmpty($key) {
    $exists = NULL;
    $value = NestedArray::getValue($this->getValues(), (array) $key, $exists);
    return !$exists || empty($value);
  }

  /**
   * Implements \Drupal\Core\Form\FormStateInterface::setValueForElement()
   */
  public function setValueForElement(array $element, $value) {
    return $this->setValue($element['#parents'], $value);
  }

}

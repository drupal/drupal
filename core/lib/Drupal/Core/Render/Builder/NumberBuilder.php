<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'number' element.
 */
class NumberBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'number'];

  /**
   * Set the title property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitle($value) {
    $this->set('title', $value);
    return $this;
  }

  /**
   * Set the value property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setValue($value) {
    $this->set('value', $value);
    return $this;
  }

  /**
   * Set the description property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDescription($value) {
    $this->set('description', $value);
    return $this;
  }

  /**
   * Set the min property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMin($value) {
    $this->set('min', $value);
    return $this;
  }

  /**
   * Set the max property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMax($value) {
    $this->set('max', $value);
    return $this;
  }

  /**
   * Set the placeholder property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setPlaceholder($value) {
    $this->set('placeholder', $value);
    return $this;
  }

  /**
   * Set the required property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setRequired($value) {
    $this->set('required', $value);
    return $this;
  }

  /**
   * Set the attributes property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAttributes($value) {
    $this->set('attributes', $value);
    return $this;
  }

  /**
   * Set the step property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setStep($value) {
    $this->set('step', $value);
    return $this;
  }

  /**
   * Set the size property on the number.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSize($value) {
    $this->set('size', $value);
    return $this;
  }

}

<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'date' element.
 */
class DateBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'date'];

  /**
   * Set the attributes property on the date.
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
   * Set the default_value property on the date.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDefaultValue($value) {
    $this->set('default_value', $value);
    return $this;
  }

  /**
   * Set the description property on the date.
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
   * Set the disabled property on the date.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDisabled($value) {
    $this->set('disabled', $value);
    return $this;
  }

  /**
   * Set the required property on the date.
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
   * Set the title property on the date.
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
   * Set the title_display property on the date.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitleDisplay($value) {
    $this->set('title_display', $value);
    return $this;
  }

}

<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'button' element.
 */
class ButtonBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'button'];

  /**
   * Set the ajax property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAjax($value) {
    $this->set('ajax', $value);
    return $this;
  }

  /**
   * Set the attributes property on the button.
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
   * Set the button_type property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setButtonType($value) {
    $this->set('button_type', $value);
    return $this;
  }

  /**
   * Set the disabled property on the button.
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
   * Set the executes_submit_callback property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setExecutesSubmitCallback($value) {
    $this->set('executes_submit_callback', $value);
    return $this;
  }

  /**
   * Set the limit_validation_errors property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLimitValidationErrors($value) {
    $this->set('limit_validation_errors', $value);
    return $this;
  }

  /**
   * Set the name property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setName($value) {
    $this->set('name', $value);
    return $this;
  }

  /**
   * Set the submit property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSubmit($value) {
    $this->set('submit', $value);
    return $this;
  }

  /**
   * Set the validate property on the button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setValidate($value) {
    $this->set('validate', $value);
    return $this;
  }

  /**
   * Set the value property on the button.
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

}

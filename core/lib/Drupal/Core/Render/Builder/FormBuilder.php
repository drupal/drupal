<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'form' element.
 */
class FormBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'form'];

  /**
   * Set the action property on the form.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAction($value) {
    $this->set('action', $value);
    return $this;
  }

  /**
   * Set the attributes property on the form.
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
   * Set the method property on the form.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMethod($value) {
    $this->set('method', $value);
    return $this;
  }

  /**
   * Set the submit property on the form.
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
   * Set the validate property on the form.
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

}

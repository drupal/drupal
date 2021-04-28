<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'password_confirm' element.
 */
class PasswordConfirmBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'password_confirm'];

  /**
   * Set the description property on the password_confirm.
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
   * Set the disabled property on the password_confirm.
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
   * Set the field_prefix property on the password_confirm.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setFieldPrefix($value) {
    $this->set('field_prefix', $value);
    return $this;
  }

  /**
   * Set the field_suffix property on the password_confirm.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setFieldSuffix($value) {
    $this->set('field_suffix', $value);
    return $this;
  }

  /**
   * Set the required property on the password_confirm.
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
   * Set the size property on the password_confirm.
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

  /**
   * Set the title property on the password_confirm.
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
   * Set the title_display property on the password_confirm.
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

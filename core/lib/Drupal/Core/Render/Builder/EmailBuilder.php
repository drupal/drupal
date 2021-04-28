<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'email' element.
 */
class EmailBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'email'];

  /**
   * Set the title property on the email.
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
   * Set the value property on the email.
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
   * Set the default_value property on the email.
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
   * Set the description property on the email.
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
   * Set the size property on the email.
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
   * Set the ajax property on the email.
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
   * Set the maxlength property on the email.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMaxlength($value) {
    $this->set('maxlength', $value);
    return $this;
  }

  /**
   * Set the placeholder property on the email.
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
   * Set the required property on the email.
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
   * Set the attributes property on the email.
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
   * Set the title_display property on the email.
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

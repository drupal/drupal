<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'tel' element.
 */
class TelBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'tel'];

  /**
   * Set the title property on the tel.
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
   * Set the value property on the tel.
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
   * Set the description property on the tel.
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
   * Set the size property on the tel.
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
   * Set the maxlength property on the tel.
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
   * Set the placeholder property on the tel.
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
   * Set the required property on the tel.
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
   * Set the attributes property on the tel.
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

}

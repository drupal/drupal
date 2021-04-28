<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'file' element.
 */
class FileBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'file'];

  /**
   * Set the attached property on the file.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAttached($value) {
    $this->set('attached', $value);
    return $this;
  }

  /**
   * Set the attributes property on the file.
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
   * Set the description property on the file.
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
   * Set the disabled property on the file.
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
   * Set the required property on the file.
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
   * Set the size property on the file.
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
   * Set the title property on the file.
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
   * Set the title_display property on the file.
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

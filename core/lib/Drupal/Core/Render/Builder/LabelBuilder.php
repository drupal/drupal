<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'label' element.
 */
class LabelBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'label'];

  /**
   * Set the title property on the label.
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
   * Set the title_display property on the label.
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

  /**
   * Set the required property on the label.
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
   * Set the attributes property on the label.
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

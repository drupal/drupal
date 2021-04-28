<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'fieldset' element.
 */
class FieldsetBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'fieldset'];

  /**
   * Set the attributes property on the fieldset.
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
   * Set the description property on the fieldset.
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
   * Set the group property on the fieldset.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setGroup($value) {
    $this->set('group', $value);
    return $this;
  }

  /**
   * Set the title property on the fieldset.
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
   * Set the title_display property on the fieldset.
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

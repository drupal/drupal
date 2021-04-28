<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'details' element.
 */
class DetailsBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'details'];

  /**
   * Set the attributes property on the details.
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
   * Set the open property on the details.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setOpen($value) {
    $this->set('open', $value);
    return $this;
  }

  /**
   * Set the description property on the details.
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
   * Set the group property on the details.
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
   * Set the title property on the details.
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
   * Set the title_display property on the details.
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

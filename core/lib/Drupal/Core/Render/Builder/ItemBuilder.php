<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'item' element.
 */
class ItemBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'item'];

  /**
   * Set the description property on the item.
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
   * Set the markup property on the item.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMarkup($value) {
    $this->set('markup', $value);
    return $this;
  }

  /**
   * Set the required property on the item.
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
   * Set the title property on the item.
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
   * Set the title_display property on the item.
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

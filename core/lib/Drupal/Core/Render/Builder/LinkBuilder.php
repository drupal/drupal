<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'link' element.
 */
class LinkBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'link'];

  /**
   * Set the title property on the link.
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
   * Set the url property on the link.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setUrl($value) {
    $this->set('url', $value);
    return $this;
  }

  /**
   * Set the options property on the link.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setOptions($value) {
    $this->set('options', $value);
    return $this;
  }

}

<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'html_tag' element.
 */
class HtmlTagBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'html_tag'];

  /**
   * Set the tag property on the html_tag.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTag($value) {
    $this->set('tag', $value);
    return $this;
  }

  /**
   * Set the attributes property on the html_tag.
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
   * Set the value property on the html_tag.
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
   * Set the value_prefix property on the html_tag.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setValuePrefix($value) {
    $this->set('value_prefix', $value);
    return $this;
  }

  /**
   * Set the value_suffix property on the html_tag.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setValueSuffix($value) {
    $this->set('value_suffix', $value);
    return $this;
  }

  /**
   * Set the noscript property on the html_tag.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setNoscript($value) {
    $this->set('noscript', $value);
    return $this;
  }

}

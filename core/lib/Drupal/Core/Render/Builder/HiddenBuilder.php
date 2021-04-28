<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'hidden' element.
 */
class HiddenBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'hidden'];

  /**
   * Set the default_value property on the hidden.
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
   * Set the value property on the hidden.
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

}

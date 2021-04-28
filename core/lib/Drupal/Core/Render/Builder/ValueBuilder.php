<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'value' element.
 */
class ValueBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'value'];

  /**
   * Set the value property on the value.
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

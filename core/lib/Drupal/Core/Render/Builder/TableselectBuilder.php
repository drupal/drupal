<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'tableselect' element.
 */
class TableselectBuilder extends Table {

  protected $renderable = ['#type' => 'tableselect'];

  /**
   * Set the ajax property on the tableselect.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAjax($value) {
    $this->set('ajax', $value);
    return $this;
  }

  /**
   * Set the default_value property on the tableselect.
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
   * Set the js_select property on the tableselect.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setJsSelect($value) {
    $this->set('js_select', $value);
    return $this;
  }

  /**
   * Set the multiple property on the tableselect.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMultiple($value) {
    $this->set('multiple', $value);
    return $this;
  }

  /**
   * Set the options property on the tableselect.
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

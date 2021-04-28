<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'table' element.
 */
class TableBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'table'];

  /**
   * Set the header property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHeader($value) {
    $this->set('header', $value);
    return $this;
  }

  /**
   * Set the rows property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setRows($value) {
    $this->set('rows', $value);
    return $this;
  }

  /**
   * Set the footer property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setFooter($value) {
    $this->set('footer', $value);
    return $this;
  }

  /**
   * Set the attributes property on the table.
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
   * Set the caption property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setCaption($value) {
    $this->set('caption', $value);
    return $this;
  }

  /**
   * Set the colgroups property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setColgroups($value) {
    $this->set('colgroups', $value);
    return $this;
  }

  /**
   * Set the sticky property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSticky($value) {
    $this->set('sticky', $value);
    return $this;
  }

  /**
   * Set the empty property on the table.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setEmpty($value) {
    $this->set('empty', $value);
    return $this;
  }

}

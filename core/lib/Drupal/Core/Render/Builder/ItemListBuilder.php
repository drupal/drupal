<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'item_list' element.
 */
class ItemListBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'item_list'];

  /**
   * Set the items property on the item_list.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setItems($value) {
    $this->set('items', $value);
    return $this;
  }

  /**
   * Set the title property on the item_list.
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
   * Set the list_type property on the item_list.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setListType($value) {
    $this->set('list_type', $value);
    return $this;
  }

  /**
   * Set the attributes property on the item_list.
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
   * Set the empty property on the item_list.
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

  /**
   * Set the context property on the item_list.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setContext($value) {
    $this->set('context', $value);
    return $this;
  }

}

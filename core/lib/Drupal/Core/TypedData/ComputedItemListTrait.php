<?php

namespace Drupal\Core\TypedData;

/**
 * Provides common functionality for computed item lists.
 *
 * @see \Drupal\Core\TypedData\ListInterface
 * @see \Drupal\Core\TypedData\Plugin\DataType\ItemList
 * @see \Drupal\Core\Field\FieldItemListInterface
 * @see \Drupal\Core\Field\FieldItemList
 *
 * @ingroup typed_data
 */
trait ComputedItemListTrait {

  /**
   * Whether the values have already been computed or not.
   *
   * @var bool
   */
  protected $valueComputed = FALSE;

  /**
   * Computes the values for an item list.
   */
  abstract protected function computeValue();

  /**
   * Ensures that values are only computed once.
   */
  protected function ensureComputedValue() {
    if ($this->valueComputed === FALSE) {
      $this->computeValue();
      $this->valueComputed = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureComputedValue();
    return parent::getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);

    // Make sure that subsequent getter calls do not try to compute the values
    // again.
    $this->valueComputed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $this->ensureComputedValue();
    return parent::getString();
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    if (!is_numeric($index)) {
      throw new \InvalidArgumentException('Unable to get a value with a non-numeric delta in a list.');
    }

    // Unlike the base implementation of
    // \Drupal\Core\TypedData\ListInterface::get(), we do not add an empty item
    // automatically because computed item lists need to behave like
    // non-computed ones. For example, calling isEmpty() on a computed item list
    // should return TRUE when the values were computed and the item list is
    // truly empty.
    // @see \Drupal\Core\TypedData\Plugin\DataType\ItemList::get().
    $this->ensureComputedValue();

    return $this->list[$index] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($index, $value) {
    $this->ensureComputedValue();
    return parent::set($index, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function appendItem($value = NULL) {
    $this->ensureComputedValue();
    return parent::appendItem($value);
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($index) {
    $this->ensureComputedValue();
    return parent::removeItem($index);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureComputedValue();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    $this->ensureComputedValue();
    return parent::offsetExists($offset);
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getIterator() {
    $this->ensureComputedValue();
    return parent::getIterator();
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function count() {
    $this->ensureComputedValue();
    return parent::count();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default values do not make sense for computed item lists. However, this
    // method can be overridden if needed.
    return $this;
  }

}

<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A generic list class.
 *
 * This class can serve as list for any type of items and is used by default.
 * Data types may specify the default list class in their definition, see
 * Drupal\Core\TypedData\Annotation\DataType.
 * Note: The class cannot be called "List" as list is a reserved PHP keyword.
 *
 * @ingroup typed_data
 *
 * @DataType(
 *   id = "list",
 *   label = @Translation("List of items"),
 *   definition_class = "\Drupal\Core\TypedData\ListDataDefinition"
 * )
 */
class ItemList extends TypedData implements \IteratorAggregate, ListInterface {

  /**
   * Numerically indexed array of items.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface[]
   */
  protected $list = [];

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $values = [];
    foreach ($this->list as $delta => $item) {
      $values[$delta] = $item->getValue();
    }
    return $values;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of values of the field items, or NULL to unset the field.
   */
  public function setValue($values, $notify = TRUE) {
    if (!isset($values) || $values === []) {
      $this->list = [];
    }
    else {
      // Only arrays with numeric keys are supported.
      if (!is_array($values)) {
        throw new \InvalidArgumentException('Cannot set a list with a non-array value.');
      }
      // Assign incoming values. Keys are renumbered to ensure 0-based
      // sequential deltas. If possible, reuse existing items rather than
      // creating new ones.
      foreach (array_values($values) as $delta => $value) {
        if (!isset($this->list[$delta])) {
          $this->list[$delta] = $this->createItem($delta, $value);
        }
        else {
          $this->list[$delta]->setValue($value, FALSE);
        }
      }
      // Truncate extraneous pre-existing values.
      $this->list = array_slice($this->list, 0, count($values));
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $strings = [];
    foreach ($this->list as $item) {
      $strings[] = $item->getString();
    }
    // Remove any empty strings resulting from empty items.
    return implode(', ', array_filter($strings, '\Drupal\Component\Utility\Unicode::strlen'));
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    if (!is_numeric($index)) {
      throw new \InvalidArgumentException('Unable to get a value with a non-numeric delta in a list.');
    }
    // Automatically create the first item for computed fields.
    // @deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0.
    // Use \Drupal\Core\TypedData\ComputedItemListTrait instead.
    if ($index == 0 && !isset($this->list[0]) && $this->definition->isComputed()) {
      @trigger_error('Automatically creating the first item for computed fields is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\TypedData\ComputedItemListTrait instead.', E_USER_DEPRECATED);
      $this->list[0] = $this->createItem(0);
    }
    return isset($this->list[$index]) ? $this->list[$index] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($index, $value) {
    if (!is_numeric($index)) {
      throw new \InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
    }
    // Ensure indexes stay sequential. We allow assigning an item at an existing
    // index, or at the next index available.
    if ($index < 0 || $index > count($this->list)) {
      throw new \InvalidArgumentException('Unable to set a value to a non-subsequent delta in a list.');
    }
    // Support setting values via typed data objects.
    if ($value instanceof TypedDataInterface) {
      $value = $value->getValue();
    }
    // If needed, create the item at the next position.
    $item = isset($this->list[$index]) ? $this->list[$index] : $this->appendItem();
    $item->setValue($value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($index) {
    if (isset($this->list) && array_key_exists($index, $this->list)) {
      // Remove the item, and reassign deltas.
      unset($this->list[$index]);
      $this->rekey($index);
    }
    else {
      throw new \InvalidArgumentException('Unable to remove item at non-existing index.');
    }
    return $this;
  }

  /**
   * Renumbers the items in the list.
   *
   * @param int $from_index
   *   Optionally, the index at which to start the renumbering, if it is known
   *   that items before that can safely be skipped (for example, when removing
   *   an item at a given index).
   */
  protected function rekey($from_index = 0) {
    // Re-key the list to maintain consecutive indexes.
    $this->list = array_values($this->list);
    // Each item holds its own index as a "name", it needs to be updated
    // according to the new list indexes.
    for ($i = $from_index; $i < count($this->list); $i++) {
      $this->list[$i]->setContext($i, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function first() {
    return $this->get(0);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    // We do not want to throw exceptions here, so we do not use get().
    return isset($this->list[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    $this->removeItem($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->get($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!isset($offset)) {
      // The [] operator has been used.
      $this->appendItem($value);
    }
    else {
      $this->set($offset, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function appendItem($value = NULL) {
    $offset = count($this->list);
    $item = $this->createItem($offset, $value);
    $this->list[$offset] = $item;
    return $item;
  }

  /**
   * Helper for creating a list item object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  protected function createItem($offset = 0, $value = NULL) {
    return $this->getTypedDataManager()->getPropertyInstance($this, $offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefinition() {
    return $this->definition->getItemDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->list as $item) {
      if ($item instanceof ComplexDataInterface || $item instanceof ListInterface) {
        if (!$item->isEmpty()) {
          return FALSE;
        }
      }
      // Other items are treated as empty if they have no value only.
      elseif ($item->getValue() !== NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function filter($callback) {
    if (isset($this->list)) {
      $removed = FALSE;
      // Apply the filter, detecting if some items were actually removed.
      $this->list = array_filter($this->list, function ($item) use ($callback, &$removed) {
        if (call_user_func($callback, $item)) {
          return TRUE;
        }
        else {
          $removed = TRUE;
        }
      });
      if ($removed) {
        $this->rekey();
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($delta) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    foreach ($this->list as $delta => $item) {
      $this->list[$delta] = clone $item;
      $this->list[$delta]->setContext($delta, $this);
    }
  }

}

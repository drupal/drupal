<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\ItemList.
 */

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
  protected $list = array();

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue() {
    $values = array();
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
    if (!isset($values) || $values === array()) {
      $this->list = array();
    }
    else {
      if (!is_array($values)) {
        throw new \InvalidArgumentException('Cannot set a list with a non-array value.');
      }

      // Clear the values of properties for which no value has been passed.
      $this->list = array_intersect_key($this->list, $values);

      // Set the values.
      foreach ($values as $delta => $value) {
        if (!is_numeric($delta)) {
          throw new \InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
        }
        elseif (!isset($this->list[$delta])) {
          $this->list[$delta] = $this->createItem($delta, $value);
        }
        else {
          $this->list[$delta]->setValue($value, FALSE);
        }
      }
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getString().
   */
  public function getString() {
    $strings = array();
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
    // Allow getting not yet existing items as well.
    // @todo: Maybe add a public createItem() method in addition?
    elseif (!isset($this->list[$index])) {
      $this->list[$index] = $this->createItem($index);
    }
    return $this->list[$index];
  }

  /**
   * {@inheritdoc}
   */
  public function set($index, $item) {
    if (is_numeric($index)) {
      // Support setting values via typed data objects.
      if ($item instanceof TypedDataInterface) {
        $item = $item->getValue();
      }
      $this->get($index)->setValue($item);
      return $this;
    }
    else {
      throw new \InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function first() {
    return $this->get(0);
  }

  /**
   * Implements \ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->list) && $this->get($offset)->getValue() !== NULL;
  }

  /**
   * Implements \ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->list[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->get($offset);
  }

  /**
   * Helper for creating a list item object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  protected function createItem($offset = 0, $value = NULL) {
    return \Drupal::typedDataManager()->getPropertyInstance($this, $offset, $value);
  }

  /**
   * Implements \Drupal\Core\TypedData\ListInterface::getItemDefinition().
   */
  public function getItemDefinition() {
    return $this->definition->getItemDefinition();
  }

  /**
   * Implements \ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    if (!isset($offset)) {
      // The [] operator has been used so point at a new entry.
      $offset = $this->list ? max(array_keys($this->list)) + 1 : 0;
    }
    $this->set($offset, $value);
  }

  /**
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new \ArrayIterator($this->list);
  }

  /**
   * Implements \Countable::count().
   */
  public function count() {
    return count($this->list);
  }

  /**
   * Implements \Drupal\Core\TypedData\ListInterface::isEmpty().
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
      // Rekey the array using array_values().
      $this->list = array_values($this->list);
      // Manually update each item's delta.
      foreach ($this->list as $delta => $item) {
        $item->setContext($delta, $this);
      }
    }
    return $this;
  }

  /**
   * Implements \Drupal\Core\TypedData\ListInterface::onChange().
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

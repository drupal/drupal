<?php

namespace Drupal\Core\TypedData;

/**
 * Interface for a list of typed data.
 *
 * A list of typed data contains only items of the same type, is ordered and may
 * contain duplicates. Note that the data type of a list is always 'list'.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\TypedData\ListDataDefinitionInterface
 *
 * @ingroup typed_data
 */
interface ListInterface extends TraversableTypedDataInterface, \ArrayAccess, \Countable {

  /**
   * Gets the data definition.
   *
   * @return \Drupal\Core\TypedData\ListDataDefinitionInterface
   *   The data definition object describing the list.
   */
  public function getDataDefinition();

  /**
   * Determines whether the list contains any non-empty items.
   *
   * @return bool
   *   TRUE if the list is empty, FALSE otherwise.
   */
  public function isEmpty();

  /**
   * Gets the definition of a contained item.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition of contained items.
   */
  public function getItemDefinition();

  /**
   * Returns the item at the specified position in this list.
   *
   * @param int $index
   *   Index of the item to return.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|null
   *   The item at the specified position in this list, or NULL if no item
   *   exists at that position.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no item can be created.
   */
  public function get($index);

  /**
   * Sets the value of the item at a given position in the list.
   *
   * @param int $index
   *   The position of the item in the list. Since a List only contains
   *   sequential, 0-based indexes, $index has to be:
   *   - Either the position of an existing item in the list. This updates the
   *   item value.
   *   - Or the next available position in the sequence of the current list
   *   indexes. This appends a new item with the provided value at the end of
   *   the list.
   * @param mixed $value
   *   The value of the item to be stored at the specified position.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If the $index is invalid (non-numeric, or pointing to an invalid
   *   position in the list).
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no item can be set.
   */
  public function set($index, $value);

  /**
   * Returns the first item in this list.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|null
   *   The first item in this list, or NULL if there are no items.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no item can be created.
   */
  public function first();

  /**
   * Returns the last item in this list.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|null
   *   The last item in this list, or NULL if there are no items.
   */
  public function last(): ?TypedDataInterface;

  /**
   * Appends a new item to the list.
   *
   * @param mixed $value
   *   The value of the new item.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The item that was appended.
   */
  public function appendItem($value = NULL);

  /**
   * Removes the item at the specified position.
   *
   * @param int $index
   *   Index of the item to remove.
   *
   * @return $this
   */
  public function removeItem($index);

  /**
   * Filters the items in the list using a custom callback.
   *
   * @param callable $callback
   *   The callback to use for filtering. Like with array_filter(), the
   *   callback is called for each item in the list. Only items for which the
   *   callback returns TRUE are preserved.
   *
   * @return $this
   */
  public function filter($callback);

}

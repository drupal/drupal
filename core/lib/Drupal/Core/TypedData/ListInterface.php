<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\ListInterface.
 */

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
 * @see \Drupal\Core\TypedData\ListDefinitionInterface
 *
 * @ingroup typed_data
 */
interface ListInterface extends TraversableTypedDataInterface, \ArrayAccess, \Countable {

  /**
   * Determines whether the list contains any non-empty items.
   *
   * @return boolean
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
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The item at the specified position in this list. An empty item is created
   *   if it does not exist yet.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no item can be created.
   */
  public function get($index);

  /**
   * Replaces the item at the specified position in this list.
   *
   * @param int $index
   *   Index of the item to replace.
   * @param mixed
   *   Item to be stored at the specified position.
   *
   * @return static
   *   Returns the list.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no item can be set.
   */
  public function set($index, $item);

  /**
   * Returns the first item in this list.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The first item in this list.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no item can be created.
   */
  public function first();

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

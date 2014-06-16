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
interface ListInterface extends TypedDataInterface, \ArrayAccess, \Countable, \Traversable {

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
   * React to changes to a child item.
   *
   * Note that this is invoked after any changes have been applied.
   *
   * @param $delta
   *   The delta of the item which is changed.
   */
  public function onChange($delta);

  /**
   * Returns the item at the specified position in this list.
   *
   * @param int $index
   *   Index of the item to return.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The item at the specified position in this list. An empty item is created
   *   if it does not exist yet.
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
   */
  public function set($index, $item);

  /**
   * Returns the first item in this list.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The first item in this list.
   */
  public function first();

}

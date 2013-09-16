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
 * contain duplicates.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
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
   * @return array
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
}

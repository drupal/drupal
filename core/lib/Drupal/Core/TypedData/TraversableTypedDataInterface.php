<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TraversableTypedDataInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * An interface for typed data objects that can be traversed.
 */
interface TraversableTypedDataInterface extends TypedDataInterface, \Traversable {

  /**
   * React to changes to a child property or item.
   *
   * Note that this is invoked after any changes have been applied.
   *
   * @param $name
   *   The name of the property or the delta of the list item which is changed.
   */
  public function onChange($name);

}

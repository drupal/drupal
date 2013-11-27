<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\ListDefinitionInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for data definitions of lists.
 *
 * This interface is present on a data definition if it describes a list. The
 * actual lists implement the \Drupal\Core\TypedData\ListInterface.
 */
interface ListDefinitionInterface extends DataDefinitionInterface {

  /**
   * Gets the data definition of an item of the list.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition describing the list items.
   */
  public function getItemDefinition();

}

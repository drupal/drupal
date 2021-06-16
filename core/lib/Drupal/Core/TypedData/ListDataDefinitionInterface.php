<?php

namespace Drupal\Core\TypedData;

/**
 * Interface for data definitions of lists.
 *
 * This interface is present on a data definition if it describes a list. The
 * actual lists implement the \Drupal\Core\TypedData\ListInterface.
 *
 * @see \Drupal\Core\TypedData\ListDefinition
 * @see \Drupal\Core\TypedData\ListInterface
 *
 * @ingroup typed_data
 */
interface ListDataDefinitionInterface extends DataDefinitionInterface {

  /**
   * Creates a new list data definition for items of the given data type.
   *
   * This method is typically used by
   * \Drupal\Core\TypedData\TypedDataManager::createListDataDefinition() to
   * build a definition object for an arbitrary item type. When the definition
   * class is known, it is recommended to directly use the static create()
   * method on that class instead; e.g.:
   * @code
   *   $list_definition = \Drupal\Core\TypedData\ListDataDefinition::create('string');
   * @endcode
   *
   * @param string $item_type
   *   The item type, for which a list data definition should be created.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If an unsupported data type gets passed to the class; e.g., 'string' to a
   *   definition class handling lists of 'field_item:* data types.
   */
  public static function createFromItemType($item_type);

  /**
   * Gets the data definition of an item of the list.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition describing the list items.
   */
  public function getItemDefinition();

}

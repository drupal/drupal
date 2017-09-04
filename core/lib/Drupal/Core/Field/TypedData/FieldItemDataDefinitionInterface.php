<?php

namespace Drupal\Core\Field\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;

/**
 * Interface for field item data definitions.
 *
 * @ingroup typed_data
 */
interface FieldItemDataDefinitionInterface extends ComplexDataDefinitionInterface {

  /**
   * Gets the field item's field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition for this field item.
   */
  public function getFieldDefinition();

  /**
   * Sets the field item's field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The new field definition to assign to this item definition.
   *
   * @return static
   *   The object itself for chaining.
   *
   * @internal
   *   Should not be used in user code. It allows to overwrite the item
   *   definition property of the cloned field definition.
   */
  public function setFieldDefinition($field_definition);

}

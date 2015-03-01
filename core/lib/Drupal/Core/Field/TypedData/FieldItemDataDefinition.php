<?php

/**
 * @file
 * Contains \Drupal\Core\Field\TypedData\FieldItemDataDefinition.
 */

namespace Drupal\Core\Field\TypedData;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * A typed data definition class for defining field items.
 *
 * This class is just a small wrapper around field definitions to expose
 * metadata about field item's via the Typed Data API. As the work is done
 * by the field definitions, this class does not benefit and thus does not
 * extend from MapDefinition or ComplexDataDefinitionBase.
 */
class FieldItemDataDefinition extends DataDefinition implements ComplexDataDefinitionInterface {

  /**
   * The field definition the item definition belongs to.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    // The data type of a field item is in the form of "field_item:$field_type".
    $parts = explode(':', $data_type, 2);
    if ($parts[0] != 'field_item') {
      throw new \InvalidArgumentException('Data type must be in the form of "field_item:FIELD_TYPE".');
    }

    $field_definition = BaseFieldDefinition::create($parts[1]);
    return $field_definition->getItemDefinition();
  }

  /**
   * Creates a new field item definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition the item definition belongs to.
   *
   * @return static
   */
  public static function create($field_definition) {
    $definition['type'] = 'field_item:' . $field_definition->getType();
    $item_definition = new static($definition);
    $item_definition->fieldDefinition = $field_definition;
    return $item_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinition($name) {
    return $this->fieldDefinition->getFieldStorageDefinition()->getPropertyDefinition($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return $this->fieldDefinition->getFieldStorageDefinition()->getPropertyDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getMainPropertyName() {
    return $this->fieldDefinition->getFieldStorageDefinition()->getMainPropertyName();
  }

  /**
   * Gets the field item's field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition for this field item.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

}

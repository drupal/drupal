<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\Field\FieldType\IntegerItem.
 */

namespace Drupal\number\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'number_integer' field type.
 *
 * @FieldType(
 *   id = "number_integer",
 *   label = @Translation("Number (integer)"),
 *   description = @Translation("This field stores a number in the database as an integer."),
 *   instance_settings = {
 *     "min" = "",
 *     "max" = "",
 *     "prefix" = "",
 *     "suffix" = ""
 *   },
 *   default_widget = "number",
 *   default_formatter = "number_integer"
 * )
 */
class IntegerItem extends NumberItemBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = DataDefinition::create('integer')
        ->setLabel(t('Integer value'));
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
        ),
      ),
    );
  }

}

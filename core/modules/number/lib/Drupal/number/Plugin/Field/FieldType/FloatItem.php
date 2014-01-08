<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\Field\FieldType\FloatItem.
 */

namespace Drupal\number\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'number_float' field type.
 *
 * @FieldType(
 *   id = "number_float",
 *   label = @Translation("Number (float)"),
 *   description = @Translation("This field stores a number in the database in a floating point format."),
 *   instance_settings = {
 *     "min" = "",
 *     "max" = "",
 *     "prefix" = "",
 *     "suffix" = ""
 *   },
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class FloatItem extends NumberItemBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = DataDefinition::create('float')
        ->setLabel(t('Float value'));
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
          'type' => 'float',
          'not null' => FALSE,
        ),
      ),
    );
  }

}

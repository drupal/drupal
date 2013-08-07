<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\field\field_type\FloatItem.
 */

namespace Drupal\number\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'number_float' field type.
 *
 * @FieldType(
 *   id = "number_float",
 *   label = @Translation("Float"),
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
      static::$propertyDefinitions['value'] = array(
        'type' => 'float',
        'label' => t('float value'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
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

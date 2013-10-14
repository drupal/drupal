<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\field\field_type\FloatItem.
 */

namespace Drupal\Core\Entity\Plugin\field\field_type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'float' entity field type.
 *
 * @FieldType(
 *   id = "float",
 *   label = @Translation("Float"),
 *   description = @Translation("An entity field containing an float value."),
 *   configurable = FALSE
 * )
 */
class FloatItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see IntegerItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'float',
        'label' => t('Float value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

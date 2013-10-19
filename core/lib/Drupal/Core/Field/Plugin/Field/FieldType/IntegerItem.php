<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\IntegerItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'integer' entity field type.
 *
 * @FieldType(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   description = @Translation("An entity field containing an integer value."),
 *   configurable = FALSE
 * )
 */
class IntegerItem extends FieldItemBase {

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
        'type' => 'integer',
        'label' => t('Integer value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

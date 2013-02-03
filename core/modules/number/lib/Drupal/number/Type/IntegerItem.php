<?php

/**
 * @file
 * Contains \Drupal\number\Type\IntegerItem.
 */

namespace Drupal\number\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'number_integer_field' entity field item.
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
   * Implements ComplexDataInterface::getPropertyDefinitions().
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

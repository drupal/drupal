<?php

/**
 * @file
 * Contains \Drupal\number\Type\DecimalItem.
 */

namespace Drupal\number\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'number_decimal_field' entity field item.
 */
class DecimalItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see DecimalItem::getPropertyDefinitions()
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
        // Decimals are represented as string in PHP.
        'type' => 'string',
        'label' => t('Decimal value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

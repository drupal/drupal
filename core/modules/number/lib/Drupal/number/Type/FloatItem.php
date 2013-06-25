<?php

/**
 * @file
 * Contains \Drupal\number\Type\FloatItem.
 */

namespace Drupal\number\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem;

/**
 * Defines the 'number_float_field' entity field item.
 */
class FloatItem extends LegacyConfigFieldItem {

  /**
   * Definitions of the contained properties.
   *
   * @see FloatItem::getPropertyDefinitions()
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
        'type' => 'float',
        'label' => t('Float value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

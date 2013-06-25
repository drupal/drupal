<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListFloatItem.
 */

namespace Drupal\options\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem;

/**
 * Defines the 'list_float' entity field item.
 */
class ListFloatItem extends LegacyConfigFieldItem {

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

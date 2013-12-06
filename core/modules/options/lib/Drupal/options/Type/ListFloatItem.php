<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListFloatItem.
 */

namespace Drupal\options\Type;

use Drupal\Core\Field\Plugin\Field\FieldType\LegacyConfigFieldItem;
use Drupal\Core\TypedData\DataDefinition;

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
      static::$propertyDefinitions['value'] = DataDefinition::create('float')
        ->setLabel(t('Float value'));
    }
    return static::$propertyDefinitions;
  }
}

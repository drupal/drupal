<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListIntegerItem.
 */

namespace Drupal\options\Type;

use Drupal\Core\Field\Plugin\Field\FieldType\LegacyConfigFieldItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'list_integer' entity field item.
 */
class ListIntegerItem extends LegacyConfigFieldItem {

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
      static::$propertyDefinitions['value'] = DataDefinition::create('integer')
        ->setLabel(t('Integer value'));
    }
    return static::$propertyDefinitions;
  }
}

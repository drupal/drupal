<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListTextItem.
 */

namespace Drupal\options\Type;

use Drupal\Core\Field\Plugin\Field\FieldType\LegacyConfigFieldItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'list_text' configurable field type.
 */
class ListTextItem extends LegacyConfigFieldItem {

  /**
   * Definitions of the contained properties.
   *
   * @see TextItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = DataDefinition::create('string')
        ->setLabel(t('Text value'));
    }
    return static::$propertyDefinitions;
  }

}

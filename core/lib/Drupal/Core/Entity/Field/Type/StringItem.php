<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\StringItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'string_field' entity field item.
 */
class StringItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see StringItem::getPropertyDefinitions()
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
        'type' => 'string',
        'label' => t('Text value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

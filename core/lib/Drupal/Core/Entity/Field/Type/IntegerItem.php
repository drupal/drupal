<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\IntegerItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'integer_field' entity field item.
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

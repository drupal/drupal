<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\DateItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'date_field' entity field item.
 */
class DateItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see DateItem::getPropertyDefinitions()
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
        'type' => 'date',
        'label' => t('Date value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

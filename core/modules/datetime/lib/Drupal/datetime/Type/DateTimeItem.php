<?php

/**
 * @file
 * Contains Drupal\datetime\Type\DateTimeItem.
 */

namespace Drupal\datetime\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'datetime' entity field item.
 */
class DateTimeItem extends FieldItemBase {

  /**
   * Field definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(self::$propertyDefinitions)) {
      self::$propertyDefinitions['value'] = array(
        'type' => 'date',
        'label' => t('Date value'),
      );
    }
    return self::$propertyDefinitions;
  }
}

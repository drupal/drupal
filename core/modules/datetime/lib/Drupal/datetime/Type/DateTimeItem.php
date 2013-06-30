<?php

/**
 * @file
 * Contains Drupal\datetime\Type\DateTimeItem.
 */

namespace Drupal\datetime\Type;

use Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem;

/**
 * Defines the 'datetime' entity field item.
 */
class DateTimeItem extends LegacyConfigFieldItem {

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
        'type' => 'datetime_iso8601',
        'label' => t('Date value'),
      );
    }
    return self::$propertyDefinitions;
  }
}

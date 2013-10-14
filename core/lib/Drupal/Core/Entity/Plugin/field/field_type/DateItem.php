<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\field\field_type\DateItem.
 */

namespace Drupal\Core\Entity\Plugin\field\field_type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'date' entity field type.
 *
 * @FieldType(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("An entity field containing a date value."),
 *   configurable = FALSE
 * )
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

<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\DateItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'date_field' entity field item.
 *
 * @DataType(
 *   id = "date_field",
 *   label = @Translation("Date field item"),
 *   description = @Translation("An entity field containing a date value."),
 *   list_class = "\Drupal\Core\Entity\Field\FieldItemList"
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

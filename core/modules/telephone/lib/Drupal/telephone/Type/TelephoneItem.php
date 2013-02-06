<?php

/**
 * @file
 * Contains \Drupal\telephone\Type\TelephoneItem.
 */

namespace Drupal\telephone\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'telephone_field' entity field items.
 */
class TelephoneItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see TelephoneItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('Telephone number'),
      );
    }
    return static::$propertyDefinitions;
  }
}

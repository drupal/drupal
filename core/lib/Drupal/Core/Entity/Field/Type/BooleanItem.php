<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\BooleanItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'boolean_field' entity field item.
 */
class BooleanItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see BooleanItem::getPropertyDefinitions()
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
        'type' => 'boolean',
        'label' => t('Boolean value'),
      );
    }
    return static::$propertyDefinitions;
  }
}

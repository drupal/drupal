<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\BooleanItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'boolean' entity field type.
 *
 * @FieldType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("An entity field containing a boolean value."),
 *   configurable = FALSE
 * )
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

<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\StringItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'string' entity field type.
 *
 * @FieldType(
 *   id = "string",
 *   label = @Translation("String"),
 *   description = @Translation("An entity field containing a string value."),
 *   configurable = FALSE
 * )
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

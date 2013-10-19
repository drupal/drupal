<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\Field\FieldType\PathItem.
 */

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'path' entity field type.
 *
 * @FieldType(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("An entity field containing a path alias and related data."),
 *   configurable = FALSE
 * )
 */
class PathItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see PathItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['alias'] = array(
        'type' => 'string',
        'label' => t('Path alias'),
      );
      static::$propertyDefinitions['pid'] = array(
        'type' => 'integer',
        'label' => t('Path id'),
      );
    }
    return static::$propertyDefinitions;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\path\Type\PathItem.
 */

namespace Drupal\path\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'path_field' entity field item.
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

<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\DataType\PathItem.
 */

namespace Drupal\path\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'path_field' entity field item.
 *
 * @DataType(
 *   id = "path_field",
 *   label = @Translation("Path field item"),
 *   description = @Translation("An entity field containing a path alias and related data."),
 *   list_class = "\Drupal\Core\Entity\Field\Field"
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

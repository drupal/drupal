<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutPath.
 */

namespace Drupal\shortcut;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * The field item for the 'path' field.
 */
class ShortcutPath extends StringItem {

  /**
   * Definitions of the contained properties.
   *
   * @see self::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = DataDefinition::create('string')
        ->setLabel(t('String value'))
        ->setComputed(TRUE)
        ->setClass('\Drupal\shortcut\ShortcutPathValue');
    }
    return static::$propertyDefinitions;
  }

}

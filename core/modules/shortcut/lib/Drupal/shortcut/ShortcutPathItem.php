<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutPathItem.
 */

namespace Drupal\shortcut;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * The field item for the 'path' field.
 */
class ShortcutPathItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('String value'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\shortcut\ShortcutPathValue');
    return $properties;
  }

}

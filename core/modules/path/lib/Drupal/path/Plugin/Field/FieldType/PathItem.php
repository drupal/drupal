<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\Field\FieldType\PathItem.
 */

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

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
      static::$propertyDefinitions['alias'] = DataDefinition::create('string')
        ->setLabel(t('Path alias'));

      static::$propertyDefinitions['pid'] = DataDefinition::create('string')
        ->setLabel(t('Path id'));
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array();
  }

}

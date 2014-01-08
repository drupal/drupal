<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\BooleanItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

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
      static::$propertyDefinitions['value'] = DataDefinition::create('boolean')
        ->setLabel(t('Boolean value'));
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
        ),
      ),
    );
  }

}

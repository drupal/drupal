<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\DateItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'date' entity field type.
 *
 * @FieldType(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("An entity field containing a date value."),
 *   configurable = FALSE
 * )
 */
class DateItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see DateItem::getPropertyDefinitions()
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
        'type' => 'date',
        'label' => t('Date value'),
      );
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
          'type' => 'varchar',
          'length' => 20,
          'not null' => FALSE,
        ),
      ),
    );
  }

}

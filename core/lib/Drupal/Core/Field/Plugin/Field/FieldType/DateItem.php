<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\DateItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

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
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('date')
      ->setLabel(t('Date value'));

    return $properties;
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

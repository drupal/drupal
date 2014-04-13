<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\DateItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'date' entity field type.
 *
 * @FieldType(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("An entity field containing a date value."),
 *   no_ui = TRUE
 * )
 */
class DateItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('date')
      ->setLabel(t('Date value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
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

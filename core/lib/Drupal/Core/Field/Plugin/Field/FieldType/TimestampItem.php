<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'timestamp' entity field type.
 *
 * @FieldType(
 *   id = "timestamp",
 *   label = @Translation("Timestamp"),
 *   description = @Translation("An entity field containing a UNIX timestamp value."),
 *   no_ui = TRUE,
 *   default_widget = "datetime_default",
 *   default_formatter = "timestamp",
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Range" = {
 *           "min" = "-2147483648",
 *           "max" = "2147483648",
 *         }
 *       }
 *     }
 *   }
 * )
 * )
 */
class TimestampItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('timestamp')
      ->setLabel(t('Timestamp value'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    );
  }

}

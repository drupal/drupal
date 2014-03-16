<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\TimestampItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'timestamp' entity field type.
 *
 * @FieldType(
 *   id = "timestamp",
 *   label = @Translation("Timestamp"),
 *   description = @Translation("An entity field containing a UNIX timestamp value."),
 *   configurable = FALSE
 * )
 */
class TimestampItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('timestamp')
      ->setLabel(t('Timestamp value'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
        ),
      ),
    );
  }
}

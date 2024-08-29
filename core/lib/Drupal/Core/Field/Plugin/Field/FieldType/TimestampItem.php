<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'timestamp' entity field type.
 */
#[FieldType(
  id: "timestamp",
  label: new TranslatableMarkup("Timestamp"),
  description: [
    new TranslatableMarkup("Ideal for using date and time calculations or comparisons"),
    new TranslatableMarkup("Date and time stored in the form of seconds since January 1, 1970 (UTC)"),
    new TranslatableMarkup("Compact and efficient for storage, sorting and calculations"),
  ],
  category: "date_time",
  default_widget: "datetime_timestamp",
  default_formatter: "timestamp",
  constraints: [
    "ComplexData" => [
      "value" => [
        "Range" => [
          "min" => "-2147483648",
          "max" => "2147483648",
        ],
      ],
    ],
  ]
)]
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
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Pick a random timestamp in the past year.
    $timestamp = \Drupal::time()->getRequestTime() - mt_rand(0, 86400 * 365);
    $values['value'] = $timestamp;
    return $values;
  }

}

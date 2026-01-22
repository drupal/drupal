<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'string_long' field type.
 */
#[FieldType(
  id: "string_long",
  label: new TranslatableMarkup("Long text"),
  description: [
    new TranslatableMarkup("Uses a text area (multiple rows) for input"),
    new TranslatableMarkup("No fixed maximum length"),
    new TranslatableMarkup("May use more storage and be slower searching and sorting"),
    new TranslatableMarkup("Use for longer text without formatting"),
  ],
  category: "plain_text",
  weight: -5,
  default_widget: "string_textarea",
  default_formatter: "basic_string",
)]
class StringLongItem extends StringItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('case_sensitive') ? 'blob' : 'text',
          'size' => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->paragraphs();
    return $values;
  }

}

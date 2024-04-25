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
  label: new TranslatableMarkup("Text (plain, long)"),
  description: [
    new TranslatableMarkup("Ideal for longer texts, like body or description"),
    new TranslatableMarkup("Supports long text without specifying a maximum length"),
    new TranslatableMarkup("May use more storage and be slower for searching and sorting"),
  ],
  category: "plain_text",
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

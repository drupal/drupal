<?php

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'text_long' field type.
 */
#[FieldType(
  id: "text_long",
  label: new TranslatableMarkup("Text (formatted, long)"),
  description: [
    new TranslatableMarkup("Ideal for longer texts, like body or description without a summary"),
    new TranslatableMarkup("Supports long text without specifying a maximum length"),
    new TranslatableMarkup("May use more storage and be slower for searching and sorting"),
  ],
  category: "formatted_text",
  default_widget: "text_textarea",
  default_formatter: "text_default",
  list_class: TextFieldItemList::class,
)]
class TextLongItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
        ],
        'format' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'format' => ['format'],
      ],
    ];
  }

}

<?php

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'text_long' field type.
 *
 * @FieldType(
 *   id = "text_long",
 *   label = @Translation("Text (formatted, long)"),
 *   description = {
 *     @Translation("Ideal for longer texts, like body or description without a summary"),
 *     @Translation("Supports long text without specifying a maximum length"),
 *     @Translation("May use more storage and be slower for searching and sorting"),
 *   },
 *   category = "formatted_text",
 *   default_widget = "text_textarea",
 *   default_formatter = "text_default",
 *   list_class = "\Drupal\text\Plugin\Field\FieldType\TextFieldItemList"
 * )
 */
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

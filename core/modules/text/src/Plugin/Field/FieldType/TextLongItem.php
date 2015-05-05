<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\Field\FieldType\TextLongItem.
 */

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'text_long' field type.
 *
 * @FieldType(
 *   id = "text_long",
 *   label = @Translation("Text (formatted, long)"),
 *   description = @Translation("This field stores a long text with a text format."),
 *   category = @Translation("Text"),
 *   default_widget = "text_textarea",
 *   default_formatter = "text_default"
 * )
 */
class TextLongItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
        ),
        'format' => array(
          'type' => 'varchar_ascii',
          'length' => 255,
        ),
      ),
      'indexes' => array(
        'format' => array('format'),
      ),
    );
  }

}

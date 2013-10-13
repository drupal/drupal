<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\field_type\TextLongItem.
 */

namespace Drupal\text\Plugin\field\field_type;

use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'text_long' field type.
 *
 * @FieldType(
 *   id = "text_long",
 *   label = @Translation("Long text"),
 *   description = @Translation("This field stores long text in the database."),
 *   instance_settings = {
 *     "text_processing" = "0"
 *   },
 *   default_widget = "text_textarea",
 *   default_formatter = "text_default"
 * )
 */
class TextLongItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'format' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'format' => array('format'),
      ),
      'foreign keys' => array(
        'format' => array(
          'table' => 'filter_format',
          'columns' => array('format' => 'format'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    $element['text_processing'] = array(
      '#type' => 'radios',
      '#title' => t('Text processing'),
      '#default_value' => $this->getFieldSetting('text_processing'),
      '#options' => array(
        t('Plain text'),
        t('Filtered text (user selects text format)'),
      ),
    );

    return $element;
  }

}

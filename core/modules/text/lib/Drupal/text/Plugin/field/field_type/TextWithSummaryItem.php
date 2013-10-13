<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\field_type\TextWithSummaryItem.
 */

namespace Drupal\text\Plugin\field\field_type;

use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'text_with_summary' field type.
 *
 * @FieldType(
 *   id = "text_with_summary",
 *   label = @Translation("Long text and summary"),
 *   description = @Translation("This field stores long text in the database along with optional summary text."),
 *   instance_settings = {
 *     "text_processing" = "1",
 *     "display_summary" = "0"
 *   },
 *   default_widget = "text_textarea_with_summary",
 *   default_formatter = "text_default"
 * )
 */
class TextWithSummaryItem extends TextItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions = parent::getPropertyDefinitions();

      static::$propertyDefinitions['summary'] = array(
        'type' => 'string',
        'label' => t('Summary text value'),
      );
      static::$propertyDefinitions['summary_processed'] = array(
        'type' => 'string',
        'label' => t('Processed summary text'),
        'description' => t('The summary text value with the text format applied.'),
        'computed' => TRUE,
        'class' => '\Drupal\text\TextProcessed',
        'settings' => array(
          'text source' => 'summary',
        ),
      );
    }
    return static::$propertyDefinitions;
  }

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
        'summary' => array(
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
  public function isEmpty() {
    $value = $this->get('summary')->getValue();
    return parent::isEmpty() && ($value === NULL || $value === '');
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();
    $settings = $this->getFieldSettings();

    $element['text_processing'] = array(
      '#type' => 'radios',
      '#title' => t('Text processing'),
      '#default_value' => $settings['text_processing'],
      '#options' => array(
        t('Plain text'),
        t('Filtered text (user selects text format)'),
      ),
    );
    $element['display_summary'] = array(
      '#type' => 'checkbox',
      '#title' => t('Summary input'),
      '#default_value' => $settings['display_summary'],
      '#description' => t('This allows authors to input an explicit summary, to be displayed instead of the automatically trimmed text when using the "Summary or trimmed" display type.'),
    );

    return $element;
  }

}

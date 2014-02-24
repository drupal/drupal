<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem.
 */

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

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
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['summary'] = DataDefinition::create('string')
      ->setLabel(t('Summary text value'));

    $properties['summary_processed'] = DataDefinition::create('string')
      ->setLabel(t('Processed summary text'))
      ->setDescription(t('The summary text value with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'summary');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
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
    $settings = $this->getSettings();

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

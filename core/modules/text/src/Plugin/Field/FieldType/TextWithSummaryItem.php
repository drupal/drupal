<?php

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'text_with_summary' field type.
 *
 * @FieldType(
 *   id = "text_with_summary",
 *   label = @Translation("Text (formatted, long, with summary)"),
 *   description = @Translation("This field stores long text with a format and an optional summary."),
 *   category = @Translation("Text"),
 *   default_widget = "text_textarea_with_summary",
 *   default_formatter = "text_default"
 * )
 */
class TextWithSummaryItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'display_summary' => 0,
      'required_summary' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['summary'] = DataDefinition::create('string')
      ->setLabel(t('Summary'));

    $properties['summary_processed'] = DataDefinition::create('string')
      ->setLabel(t('Processed summary'))
      ->setDescription(t('The summary text with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'summary');

    return $properties;
  }

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
        'summary' => [
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
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $element['display_summary'] = [
      '#type' => 'checkbox',
      '#title' => t('Summary input'),
      '#default_value' => $settings['display_summary'],
      '#description' => t('This allows authors to input an explicit summary, to be displayed instead of the automatically trimmed text when using the "Summary or trimmed" display type.'),
    ];

    $element['required_summary'] = [
      '#type' => 'checkbox',
      '#title' => t('Require summary'),
      '#description' => t('The summary will also be visible when marked as required.'),
      '#default_value' => $settings['required_summary'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    if ($this->getSetting('required_summary')) {
      $manager = $this->getTypedDataManager()->getValidationConstraintManager();
      $constraints[] = $manager->create('ComplexData', [
        'summary' => [
          'NotNull' => [
            'message' => $this->t('The summary field is required for @name', ['@name' => $this->getFieldDefinition()->getLabel()]),
          ],
        ],
      ]);
    }
    return $constraints;
  }

}

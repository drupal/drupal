<?php

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text' field type.
 *
 * @FieldType(
 *   id = "text",
 *   label = @Translation("Text (formatted)"),
 *   description = @Translation("This field stores a text with a text format."),
 *   category = @Translation("Text"),
 *   default_widget = "text_textfield",
 *   default_formatter = "text_default",
 *   list_class = "\Drupal\text\Plugin\Field\FieldType\TextFieldItemList"
 * )
 */
class TextItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => $field_definition->getSetting('max_length'),
        ],
        'format' => [
          'type' => 'varchar',
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
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => $this->t('%name: the text may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => $this->t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];
    $element += parent::storageSettingsForm($form, $form_state, $has_data);

    return $element;
  }

}

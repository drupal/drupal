<?php

namespace Drupal\text\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Base class for 'text' configurable field types.
 */
abstract class TextItemBase extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['allowed_formats' => []] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['allowed_formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed text formats'),
      '#options' => $this->get('format')->getPossibleOptions(),
      '#default_value' => !empty($settings['allowed_formats']) ? $settings['allowed_formats'] : [],
      '#description' => $this->t('Select the allowed text formats. If no formats are selected, all available text formats will be displayed to the user.'),
      '#element_validate' => [[static::class, 'validateAllowedFormats']],
    ];

    return $element;
  }

  /**
   * Render API callback: Processes the allowed formats value.
   *
   * Ensure the element's value is an indexed array of selected format IDs.
   * This function is assigned as an #element_validate callback.
   *
   * @see static::fieldSettingsForm()
   */
  public static function validateAllowedFormats(array &$element, FormStateInterface $form_state) {
    $value = array_values(array_filter($form_state->getValue($element['#parents'])));
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    // Add explicitly allowed formats as config dependencies.
    $format_dependencies = [];
    $dependencies = parent::calculateDependencies($field_definition);
    if (!is_null($field_definition->getSetting('allowed_formats'))) {
      $format_dependencies = array_map(function (string $format_id) {
        return 'filter.format.' . $format_id;
      }, $field_definition->getSetting('allowed_formats'));
    }
    $config = $dependencies['config'] ?? [];
    $dependencies['config'] = array_merge($config, $format_dependencies);
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text'))
      ->setRequired(TRUE);

    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'))
      ->setSetting('allowed_formats', $field_definition->getSetting('allowed_formats'));

    $properties['processed'] = DataDefinition::create('string')
      ->setLabel(t('Processed text'))
      ->setDescription(t('The text with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'value')
      ->setInternal(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // @todo: Add in the filter default format here.
    $this->setValue(['format' => NULL], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Unset processed properties that are affected by the change.
    foreach ($this->definition->getPropertyDefinitions() as $property => $definition) {
      if ($definition->getClass() == '\Drupal\text\TextProcessed') {
        if ($property_name == 'format' || ($definition->getSetting('text source') == $property_name)) {
          $this->writePropertyValue($property, NULL);
        }
      }
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();

    if (empty($settings['max_length'])) {
      // Textarea handling
      $value = $random->paragraphs();
    }
    else {
      // Textfield handling.
      $max = ceil($settings['max_length'] / 3);
      $value = substr($random->sentences(mt_rand(1, $max), FALSE), 0, $settings['max_length']);
    }

    $values = [
      'value' => $value,
      'summary' => $value,
      'format' => filter_fallback_format(),
    ];
    return $values;
  }

}

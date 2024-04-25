<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'float' field type.
 */
#[FieldType(
  id: "float",
  label: new TranslatableMarkup("Number (float)"),
  description: [
    new TranslatableMarkup("In most instances, it is best to use Number (decimal) instead, as decimal numbers stored as floats may contain errors in precision"),
    new TranslatableMarkup("This type of field offers faster processing and more compact storage, but the differences are typically negligible on modern sites"),
    new TranslatableMarkup("For example, 123.4 km when used in imprecise contexts such as a walking trail distance"),
  ],
  category: "number",
  weight: -10,
  default_widget: "number",
  default_formatter: "number_decimal"
)]
class FloatItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Float'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'float',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $element['min']['#step'] = 'any';
    $element['max']['#step'] = 'any';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $precision = rand(10, 32);
    $scale = rand(0, 2);
    $max = is_numeric($settings['max']) ? $settings['max'] : pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ? $settings['min'] : -pow(10, ($precision - $scale)) + 1;
    // @see "Example #1 Calculate a random floating-point number" in
    // http://php.net/manual/function.mt-getrandmax.php
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $values['value'] = self::truncateDecimal($random_decimal, $scale);
    return $values;
  }

}

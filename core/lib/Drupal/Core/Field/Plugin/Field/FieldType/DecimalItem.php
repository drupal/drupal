<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'decimal' field type.
 *
 * @FieldType(
 *   id = "decimal",
 *   label = @Translation("Number (decimal)"),
 *   description = {
 *     @Translation("Ideal for exact counts and measures (prices, temperatures, distances, volumes, etc.)"),
 *     @Translation("Stores a number in the database in a fixed decimal format"),
 *     @Translation("For example, 12.34 km or € when used for further detailed calculations (such as summing many of these)"),
 *   },
 *   category = "number",
 *   weight = -30,
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class DecimalItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'precision' => 10,
      'scale' => 2,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Decimal value'))
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
          'type' => 'numeric',
          'precision' => $field_definition->getSetting('precision'),
          'scale' => $field_definition->getSetting('scale'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $settings = $this->getSettings();

    $element['precision'] = [
      '#type' => 'number',
      '#title' => $this->t('Precision'),
      '#min' => 10,
      '#max' => 32,
      '#default_value' => $settings['precision'],
      '#description' => $this->t('The total number of digits to store in the database, including those to the right of the decimal.'),
      '#disabled' => $has_data,
    ];

    $element['scale'] = [
      '#type' => 'number',
      '#title' => $this->t('Scale', [], ['context' => 'decimal places']),
      '#min' => 0,
      '#max' => 10,
      '#default_value' => $settings['scale'],
      '#description' => $this->t('The number of digits to the right of the decimal.'),
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['min']['#step'] = pow(0.1, $settings['scale']);
    $element['max']['#step'] = pow(0.1, $settings['scale']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = round($this->value, $this->getSetting('scale'));
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $precision = $settings['precision'] ?: 10;
    $scale = $settings['scale'] ?: 2;
    // $precision - $scale is the number of digits on the left of the decimal
    // point.
    // The maximum number you can get with 3 digits is 10^3 - 1 --> 999.
    // The minimum number you can get with 3 digits is -1 * (10^3 - 1).
    $max = is_numeric($settings['max']) ? $settings['max'] : pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ? $settings['min'] : -pow(10, ($precision - $scale)) + 1;

    // Get the number of decimal digits for the $max
    $decimal_digits = self::getDecimalDigits($max);
    // Do the same for the min and keep the higher number of decimal digits.
    $decimal_digits = max(self::getDecimalDigits($min), $decimal_digits);
    // If $min = 1.234 and $max = 1.33 then $decimal_digits = 3
    $scale = rand($decimal_digits, $scale);

    // @see "Example #1 Calculate a random floating-point number" in
    // http://php.net/manual/function.mt-getrandmax.php
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $values['value'] = self::truncateDecimal($random_decimal, $scale);
    return $values;
  }

  /**
   * Helper method to get the number of decimal digits out of a decimal number.
   *
   * @param int $decimal
   *   The number to calculate the number of decimals digits from.
   *
   * @return int
   *   The number of decimal digits.
   */
  protected static function getDecimalDigits($decimal) {
    $digits = 0;
    while ($decimal - round($decimal)) {
      $decimal *= 10;
      $digits++;
    }
    return $digits;
  }

}

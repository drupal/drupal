<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'decimal' field type.
 *
 * @FieldType(
 *   id = "decimal",
 *   label = @Translation("Number (decimal)"),
 *   description = @Translation("This field stores a number in the database in a fixed decimal format."),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class DecimalItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'precision' => 10,
      'scale' => 2,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}

   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Decimal value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'numeric',
          'precision' => $field_definition->getSetting('precision'),
          'scale' => $field_definition->getSetting('scale'),
        )
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();
    $settings = $this->getSettings();

    $range = range(10, 32);
    $element['precision'] = array(
      '#type' => 'select',
      '#title' => t('Precision'),
      '#options' => array_combine($range, $range),
      '#default_value' => $settings['precision'],
      '#description' => t('The total number of digits to store in the database, including those to the right of the decimal.'),
      '#disabled' => $has_data,
    );
    $range = range(0, 10);
    $element['scale'] = array(
      '#type' => 'select',
      '#title' => t('Scale', array(), array('decimal places')),
      '#options' => array_combine($range, $range),
      '#default_value' => $settings['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
      '#disabled' => $has_data,
    );

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
    $max = is_numeric($settings['max']) ?: pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ?: -pow(10, ($precision - $scale)) + 1;

    // Get the number of decimal digits for the $max
    $decimal_digits = self::getDecimalDigits($max);
    // Do the same for the min and keep the higher number of decimal digits.
    $decimal_digits = max(self::getDecimalDigits($min), $decimal_digits);
    // If $min = 1.234 and $max = 1.33 then $decimal_digits = 3
    $scale = rand($decimal_digits, $scale);

    // @see "Example #1 Calculate a random floating-point number" in
    // http://php.net/manual/en/function.mt-getrandmax.php
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

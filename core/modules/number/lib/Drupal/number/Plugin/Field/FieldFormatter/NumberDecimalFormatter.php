<?php

/**
 * @file
 * Definition of Drupal\number\Plugin\field\formatter\NumberDecimalFormatter.
 */

namespace Drupal\number\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'number_decimal' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "number_decimal",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "number_decimal",
 *     "number_float"
 *   },
 *   settings = {
 *     "thousand_separator" = "",
 *     "decimal_separator" = ".",
 *     "scale" = "2",
 *     "prefix_suffix" = "TRUE"
 *   }
 * )
 */
class NumberDecimalFormatter extends DefaultNumberFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['decimal_separator'] = array(
      '#type' => 'select',
      '#title' => t('Decimal marker'),
      '#options' => array('.' => t('Decimal point'), ',' => t('Comma')),
      '#default_value' => $this->getSetting('decimal_separator'),
      'weight' => 5,
    );
    $elements['scale'] = array(
      '#type' => 'select',
      '#title' => t('Scale'),
      '#options' => drupal_map_assoc(range(0, 10)),
      '#default_value' => $this->getSetting('scale'),
      '#description' => t('The number of digits to the right of the decimal.'),
      'weight' => 6,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    return number_format($number, $this->getSetting('scale'), $this->getSetting('decimal_separator'), $this->getSetting('thousand_separator'));
  }

}

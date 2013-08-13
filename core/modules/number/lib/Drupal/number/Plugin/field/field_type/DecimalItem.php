<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\field\field_type\DecimalItem.
 */

namespace Drupal\number\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\FieldInterface;
use Drupal\Component\Utility\MapArray;

/**
 * Plugin implementation of the 'number_decimal' field type.
 *
 * @FieldType(
 *   id = "number_decimal",
 *   label = @Translation("Decimal"),
 *   description = @Translation("This field stores a number in the database in a fixed decimal format."),
 *   settings = {
 *     "precision" = "10",
 *     "scale" = "2"
 *   },
 *   instance_settings = {
 *     "min" = "",
 *     "max" = "",
 *     "prefix" = "",
 *     "suffix" = ""
 *   },
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class DecimalItem extends NumberItemBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('Decimal value'),
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
          'type' => 'numeric',
          'precision' => $field->settings['precision'],
          'scale' => $field->settings['scale'],
          'not null' => FALSE
        )
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element = array();
    $settings = $this->getFieldSettings();

    $element['precision'] = array(
      '#type' => 'select',
      '#title' => t('Precision'),
      '#options' => MapArray::copyValuesToKeys(range(10, 32)),
      '#default_value' => $settings['precision'],
      '#description' => t('The total number of digits to store in the database, including those to the right of the decimal.'),
      '#disabled' => $has_data,
    );
    $element['scale'] = array(
      '#type' => 'select',
      '#title' => t('Scale'),
      '#options' => MapArray::copyValuesToKeys(range(0, 10)),
      '#default_value' => $settings['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
      '#disabled' => $has_data,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = round($this->value, $this->getFieldSetting('scale'));
  }

}

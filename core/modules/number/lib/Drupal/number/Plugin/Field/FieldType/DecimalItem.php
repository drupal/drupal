<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\Field\FieldType\DecimalItem.
 */

namespace Drupal\number\Plugin\Field\FieldType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\MapArray;

/**
 * Plugin implementation of the 'number_decimal' field type.
 *
 * @FieldType(
 *   id = "number_decimal",
 *   label = @Translation("Number (decimal)"),
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
      static::$propertyDefinitions['value'] = DataDefinition::create('string')
        ->setLabel(t('Decimal value'));
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'numeric',
          'precision' => $field_definition->settings['precision'],
          'scale' => $field_definition->settings['scale'],
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
      '#title' => t('Scale', array(), array('decimal places')),
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

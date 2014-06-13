<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
  public static function defaultSettings() {
    return array(
      'precision' => 10,
      'scale' => 2,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}

   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Decimal value'));

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
          'not null' => FALSE
        )
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array &$form_state, $has_data) {
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
  public function preSave() {
    $this->value = round($this->value, $this->getSetting('scale'));
  }

}

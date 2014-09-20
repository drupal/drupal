<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for numeric configurable field types.
 */
abstract class NumericItemBase extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $settings = $this->getSettings();

    $element['min'] = array(
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $settings['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
    );
    $element['max'] = array(
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $settings['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
    );
    $element['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    );
    $element['suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (empty($this->value) && (string) $this->value !== '0') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $settings = $this->getSettings();
    $label = $this->getFieldDefinition()->getLabel();

    if (!empty($settings['min'])) {
      $min = $settings['min'];
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Range' => array(
            'min' => $min,
            'minMessage' => t('%name: the value may be no less than %min.', array('%name' => $label, '%min' => $min)),
          )
        ),
      ));
    }

    if (!empty($settings['max'])) {
      $max = $settings['max'];
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Range' => array(
            'max' => $max,
            'maxMessage' => t('%name: the value may be no greater than %max.', array('%name' => $label, '%max' => $max)),
          )
        ),
      ));
    }

    return $constraints;
  }

  /**
   * Helper method to truncate a decimal number to a given number of decimals.
   *
   * @param float $decimal
   *   Decimal number to truncate.
   * @param int $num
   *   Number of digits the output will have.
   *
   * @return float
   *   Decimal number truncated.
   */
  protected static function truncateDecimal($decimal, $num) {
    return floor($decimal * pow(10, $num)) / pow(10, $num);
  }

}

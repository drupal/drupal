<?php

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
    return [
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $element['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum'),
      '#default_value' => $settings['min'],
      '#element_validate' => [[static::class, 'validateMinAndMaxConfig']],
      '#description' => $this->t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
    ];
    $element['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum'),
      '#default_value' => $settings['max'],
      '#description' => $this->t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
    ];
    $element['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];
    $element['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

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

    if (isset($settings['min']) && $settings['min'] !== '') {
      $min = $settings['min'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'min' => $min,
            'minMessage' => $this->t('%name: the value may be no less than %min.', ['%name' => $label, '%min' => $min]),
          ],
        ],
      ]);
    }

    if (isset($settings['max']) && $settings['max'] !== '') {
      $max = $settings['max'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'max' => $max,
            'maxMessage' => $this->t('%name: the value may be no greater than %max.', [
              '%name' => $label,
              '%max' => $max,
            ]),
          ],
        ],
      ]);
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

  /**
   * Validates that the minimum value is less than the maximum.
   *
   * @param array[] $element
   *   The numeric element to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array[] $complete_form
   *   The complete form structure.
   */
  public static function validateMinAndMaxConfig(array &$element, FormStateInterface &$form_state, array &$complete_form): void {
    $settingsValue = $form_state->getValue('settings');

    // Ensure that the minimum and maximum are numeric.
    $minValue = is_numeric($settingsValue['min']) ? (float) $settingsValue['min'] : NULL;
    $maxValue = is_numeric($settingsValue['max']) ? (float) $settingsValue['max'] : NULL;

    // Only proceed with validation if both values are numeric.
    if ($minValue === NULL || $maxValue === NULL) {
      return;
    }

    if ($minValue > $maxValue) {
      $form_state->setError($element, t('The minimum value must be less than or equal to %max.', ['%max' => $maxValue]));
      return;
    }
  }

}

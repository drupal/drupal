<?php

namespace Drupal\datetime\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity datetime field.
 */
class DateTimeFieldItemList extends FieldItemList {

  /**
   * Defines the default value as now.
   */
  const DEFAULT_VALUE_NOW = 'now';

  /**
   * Defines the default value as relative.
   */
  const DEFAULT_VALUE_CUSTOM = 'relative';

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      $default_value = $this->getFieldDefinition()->getDefaultValueLiteral();

      $element = [
        '#parents' => ['default_value_input'],
        'default_date_type' => [
          '#type' => 'select',
          '#title' => $this->t('Default date'),
          '#description' => $this->t('Set a default value for this date.'),
          '#default_value' => $default_value[0]['default_date_type'] ?? '',
          '#options' => [
            static::DEFAULT_VALUE_NOW => $this->t('Current date'),
            static::DEFAULT_VALUE_CUSTOM => $this->t('Relative date'),
          ],
          '#empty_value' => '',
        ],
        'default_date' => [
          '#type' => 'textfield',
          '#title' => $this->t('Relative default value'),
          '#description' => $this->t("Describe a time by reference to the current day, like '+90 days' (90 days from the day the field is created) or '+1 Saturday' (the next Saturday). See <a href=\"http://php.net/manual/function.strtotime.php\">strtotime</a> for more details."),
          '#default_value' => (isset($default_value[0]['default_date_type']) && $default_value[0]['default_date_type'] == static::DEFAULT_VALUE_CUSTOM) ? $default_value[0]['default_date'] : '',
          '#states' => [
            'visible' => [
              ':input[id="edit-default-value-input-default-date-type"]' => ['value' => static::DEFAULT_VALUE_CUSTOM],
            ],
          ],
        ],
      ];

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_date_type']) == static::DEFAULT_VALUE_CUSTOM) {
      $is_strtotime = @strtotime($form_state->getValue(['default_value_input', 'default_date']));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_date', $this->t('The relative date value entered is invalid.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_date_type'])) {
      if ($form_state->getValue(['default_value_input', 'default_date_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_date'], static::DEFAULT_VALUE_NOW);
      }
      return [$form_state->getValue('default_value_input')];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if (isset($default_value[0]['default_date_type'])) {
      if ($definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
        // A default date only value should be in the format used for date
        // storage but in the user's local timezone.
        $date = new DrupalDateTime($default_value[0]['default_date'], date_default_timezone_get());
        $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
      }
      else {
        // A default date+time value should be in the format and timezone used
        // for date storage.
        $date = new DrupalDateTime($default_value[0]['default_date'], DateTimeItemInterface::STORAGE_TIMEZONE);
        $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
      }
      $value = $date->format($format);
      // We only provide a default value for the first item, as do all fields.
      // Otherwise, there is no way to clear out unwanted values on multiple
      // value fields.
      $default_value = [
        [
          'value' => $value,
          'date' => $date,
        ],
      ];
    }
    return $default_value;
  }

}

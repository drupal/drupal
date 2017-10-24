<?php

namespace Drupal\datetime_range\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Represents a configurable entity daterange field.
 */
class DateRangeFieldItemList extends DateTimeFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      $default_value = $this->getFieldDefinition()->getDefaultValueLiteral();

      $element = parent::defaultValuesForm($form, $form_state);

      $element['default_date_type']['#title'] = $this->t('Default start date');
      $element['default_date_type']['#description'] = $this->t('Set a default value for the start date.');

      $element['default_end_date_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Default end date'),
        '#description' => $this->t('Set a default value for the end date.'),
        '#default_value' => isset($default_value[0]['default_end_date_type']) ? $default_value[0]['default_end_date_type'] : '',
        '#options' => [
          static::DEFAULT_VALUE_NOW => $this->t('Current date'),
          static::DEFAULT_VALUE_CUSTOM => $this->t('Relative date'),
        ],
        '#empty_value' => '',
      ];

      $element['default_end_date'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Relative default value'),
        '#description' => $this->t("Describe a time by reference to the current day, like '+90 days' (90 days from the day the field is created) or '+1 Saturday' (the next Saturday). See <a href=\"http://php.net/manual/function.strtotime.php\">strtotime</a> for more details."),
        '#default_value' => (isset($default_value[0]['default_end_date_type']) && $default_value[0]['default_end_date_type'] == static::DEFAULT_VALUE_CUSTOM) ? $default_value[0]['default_end_date'] : '',
        '#states' => [
          'visible' => [
            ':input[id="edit-default-value-input-default-end-date-type"]' => ['value' => static::DEFAULT_VALUE_CUSTOM],
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
        $form_state->setErrorByName('default_value_input][default_date', $this->t('The relative start date value entered is invalid.'));
      }
    }

    if ($form_state->getValue(['default_value_input', 'default_end_date_type']) == static::DEFAULT_VALUE_CUSTOM) {
      $is_strtotime = @strtotime($form_state->getValue(['default_value_input', 'default_end_date']));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_end_date', $this->t('The relative end date value entered is invalid.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_date_type']) || $form_state->getValue(['default_value_input', 'default_end_date_type'])) {
      if ($form_state->getValue(['default_value_input', 'default_date_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_date'], static::DEFAULT_VALUE_NOW);
      }
      if ($form_state->getValue(['default_value_input', 'default_end_date_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_end_date'], static::DEFAULT_VALUE_NOW);
      }
      return [$form_state->getValue('default_value_input')];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // Explicitly call the base class so that we can get the default value
    // types.
    $default_value = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    // Allow either the start or end date to have a default, but not require
    // defaults for both.
    if (!empty($default_value[0]['default_date_type']) || !empty($default_value[0]['default_end_date_type'])) {
      // A default value should be in the format and timezone used for date
      // storage. All-day ranges are stored the same as date+time ranges.  We
      // only provide a default value for the first item, as do all fields.
      // Otherwise, there is no way to clear out unwanted values on multiple
      // value fields.
      $storage_format = $definition->getSetting('datetime_type') == DateRangeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
      $default_values = [[]];

      if (!empty($default_value[0]['default_date_type'])) {
        $start_date = new DrupalDateTime($default_value[0]['default_date'], DateTimeItemInterface::STORAGE_TIMEZONE);
        $start_value = $start_date->format($storage_format);
        $default_values[0]['value'] = $start_value;
        $default_values[0]['start_date'] = $start_date;
      }

      if (!empty($default_value[0]['default_end_date_type'])) {
        $end_date = new DrupalDateTime($default_value[0]['default_end_date'], DateTimeItemInterface::STORAGE_TIMEZONE);
        $end_value = $end_date->format($storage_format);
        $default_values[0]['end_value'] = $end_value;
        $default_values[0]['end_date'] = $end_date;
      }

      $default_value = $default_values;
    }

    return $default_value;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList.
 */

namespace Drupal\datetime\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
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
    if (empty($this->getFieldDefinition()->default_value_callback)) {
      $default_value = $this->getFieldDefinition()->default_value;

      $element = array(
        '#parents' => array('default_value_input'),
        'default_date_type' => array(
          '#type' => 'select',
          '#title' => t('Default date'),
          '#description' => t('Set a default value for this date.'),
          '#default_value' => isset($default_value[0]['default_date_type']) ? $default_value[0]['default_date_type'] : '',
          '#options' => array(
            static::DEFAULT_VALUE_NOW => t('Current date'),
            static::DEFAULT_VALUE_CUSTOM => t('Relative date'),
          ),
          '#empty_value' => '',
        ),
        'default_date' => array(
          '#type' => 'textfield',
          '#title' => t('Relative default value'),
          '#description' => t("Describe a time by reference to the current day, like '+90 days' (90 days from the day the field is created) or '+1 Saturday' (the next Saturday). See <a href=\"@url\">@strtotime</a> for more details.", array('@strtotime' => 'strtotime', '@url' => 'http://www.php.net/manual/en/function.strtotime.php')),
          '#default_value' => (isset($default_value[0]['default_date_type']) && $default_value[0]['default_date_type'] == static::DEFAULT_VALUE_CUSTOM) ? $default_value[0]['default_date'] : '',
          '#states' => array(
            'visible' => array(
              ':input[id="edit-default-value-input-default-date-type"]' => array('value' => static::DEFAULT_VALUE_CUSTOM),
            )
          )
        )
      );

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_date_type']) == static::DEFAULT_VALUE_CUSTOM) {
      $is_strtotime = @strtotime($form_state->getValue(array('default_value_input', 'default_date')));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_date', t('The relative date value entered is invalid.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(array('default_value_input', 'default_date_type'))) {
      if ($form_state->getValue(array('default_value_input', 'default_date_type')) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_date'], static::DEFAULT_VALUE_NOW);
      }
      return array($form_state->getValue('default_value_input'));
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, ContentEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if (isset($default_value[0]['default_date_type'])) {
      // A default value should be in the format and timezone used for date
      // storage.
      $date = new DrupalDateTime($default_value[0]['default_date'], DATETIME_STORAGE_TIMEZONE);
      $storage_format = $definition->getSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE ? DATETIME_DATE_STORAGE_FORMAT: DATETIME_DATETIME_STORAGE_FORMAT;
      $value = $date->format($storage_format);
      // We only provide a default value for the first item, as do all fields.
      // Otherwise, there is no way to clear out unwanted values on multiple value
      // fields.
      $default_value =  array(
        array(
          'value' => $value,
          'date' => $date,
        )
      );
    }
    return $default_value;
  }

}

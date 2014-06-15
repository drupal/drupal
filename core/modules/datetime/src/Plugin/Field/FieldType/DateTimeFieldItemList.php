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

/**
 * Represents a configurable entity datetime field.
 */
class DateTimeFieldItemList extends FieldItemList {

  /**
   * Defines the default value as now.
   */
  const DEFAULT_VALUE_NOW = 'now';

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) {
    if (empty($this->getFieldDefinition()->default_value_function)) {
      $default_value = $this->getFieldDefinition()->default_value;

      $element = array(
        '#parents' => array('default_value_input'),
        'default_date' => array(
          '#type' => 'select',
          '#title' => t('Default date'),
          '#description' => t('Set a default value for this date.'),
          '#default_value' => isset($default_value[0]['default_date']) ? $default_value[0]['default_date'] : '',
          '#options' => array(static::DEFAULT_VALUE_NOW => t('The current date')),
          '#empty_value' => '',
        )
      );

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, array &$form_state) { }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, array &$form_state) {
    if ($form_state['values']['default_value_input']['default_date']) {
      return array($form_state['values']['default_value_input']);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, ContentEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if (isset($default_value[0]['default_date']) && $default_value[0]['default_date'] == static::DEFAULT_VALUE_NOW) {
      // A default value should be in the format and timezone used for date
      // storage.
      $date = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);
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

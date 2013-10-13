<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\field\field_type\DateTimeItem.
 */

namespace Drupal\datetime\Plugin\field\field_type;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Field\PrepareCacheInterface;
use Drupal\field\FieldInterface;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;

/**
 * Plugin implementation of the 'datetime' field type.
 *
 * @FieldType(
 *   id = "datetime",
 *   label = @Translation("Date"),
 *   description = @Translation("Create and store date values."),
 *   settings = {
 *     "datetime_type" = "datetime"
 *   },
 *   instance_settings = {
 *     "default_value" = "now"
 *   },
 *   default_widget = "datetime_default",
 *   default_formatter = "datetime_default"
 * )
 */
class DateTimeItem extends ConfigFieldItemBase implements PrepareCacheInterface {

  /**
   * Field definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'datetime_iso8601',
        'label' => t('Date value'),
      );
      static::$propertyDefinitions['date'] = array(
        'type' => 'datetime_computed',
        'label' => t('Computed date'),
        'description' => t('The computed DateTime object.'),
        'computed' => TRUE,
        'class' => '\Drupal\datetime\DateTimeComputed',
        'settings' => array(
          'date source' => 'value',
        ),
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
          'description' => 'The date value.',
          'type' => 'varchar',
          'length' => 20,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element = array();

    $element['datetime_type'] = array(
      '#type' => 'select',
      '#title' => t('Date type'),
      '#description' => t('Choose the type of date to create.'),
      '#default_value' => $this->getFieldSetting('datetime_type'),
      '#options' => array(
        'datetime' => t('Date and time'),
        'date' => t('Date only'),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    $element['default_value'] = array(
      '#type' => 'select',
      '#title' => t('Default date'),
      '#description' => t('Set a default value for this date.'),
      '#default_value' => $this->getFieldSetting('default_value'),
      '#options' => array('blank' => t('No default value'), 'now' => t('The current date')),
      '#weight' => 1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheData() {
    $data = $this->getValue();
    // The function generates a Date object for each field early so that it is
    // cached in the field cache. This avoids the need to generate the object
    // later. The date will be retrieved in UTC, the local timezone adjustment
    // must be made in real time, based on the preferences of the site and user.
    if (!empty($data['value'])) {
      $data['date'] = $this->date;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name) {
    parent::onChange($property_name);

    // Enforce that the computed date is recalculated.
    if ($property_name == 'value') {
      $this->date = NULL;
    }
  }

}

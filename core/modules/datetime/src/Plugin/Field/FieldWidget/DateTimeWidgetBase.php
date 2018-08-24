<?php

namespace Drupal\datetime\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Base class for the 'datetime_*' widgets.
 */
class DateTimeWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = [
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#required' => $element['#required'],
    ];

    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      // A date-only field should have no timezone conversion performed, so
      // use the same timezone as for storage.
      $element['value']['#date_timezone'] = DateTimeItemInterface::STORAGE_TIMEZONE;
    }

    if ($items[$delta]->date) {
      $date = $items[$delta]->date;
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      $date->setTimezone(new \DateTimeZone($element['value']['#date_timezone']));
      $element['value']['#default_value'] = $this->createDefaultValue($date, $element['value']['#date_timezone']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.

    $datetime_type = $this->getFieldSetting('datetime_type');
    if ($datetime_type === DateTimeItem::DATETIME_TYPE_DATE) {
      $storage_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    }
    else {
      $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    }

    $storage_timezone = new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE);

    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item['value'];

        // Adjust the date for storage.
        $item['value'] = $date->setTimezone($storage_timezone)->format($storage_format);
      }
    }
    return $values;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
  protected function createDefaultValue($date, $timezone) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
    }
    $date->setTimezone(new \DateTimeZone($timezone));
    return $date;
  }

}

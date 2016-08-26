<?php

namespace Drupal\datetime_range;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Provides friendly methods for datetime range.
 */
trait DateTimeRangeTrait {

  /**
   * Creates a render array from a date object.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   *
   * @return array
   *   A render array.
   */
  protected function buildDate(DrupalDateTime $date) {
    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      // A date without time will pick up the current time, use the default.
      datetime_date_default_time($date);
    }
    $this->setTimeZone($date);

    $build = [
      '#plain_text' => $this->formatDate($date),
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Creates a render array from a date object with ISO date attribute.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   *
   * @return array
   *   A render array.
   */
  protected function buildDateWithIsoAttribute(DrupalDateTime $date) {
    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      // A date without time will pick up the current time, use the default.
      datetime_date_default_time($date);
    }

    // Create the ISO date in Universal Time.
    $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

    $this->setTimeZone($date);

    $build = [
      '#theme' => 'time',
      '#text' => $this->formatDate($date),
      '#html' => FALSE,
      '#attributes' => [
        'datetime' => $iso_date,
      ],
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

}

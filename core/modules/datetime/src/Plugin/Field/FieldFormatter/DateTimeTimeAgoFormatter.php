<?php

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampAgoFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Time ago' formatter for 'datetime' fields.
 */
#[FieldFormatter(
  id: 'datetime_time_ago',
  label: new TranslatableMarkup('Time ago'),
  field_types: [
    'datetime',
  ],
)]
class DateTimeTimeAgoFormatter extends TimestampAgoFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $date = $item->date;
      $output = [];
      if (!empty($item->date)) {
        $output = $this->formatDate($date);
      }
      $elements[$delta] = $output;
    }

    return $elements;
  }

  /**
   * Formats a date/time as a time interval.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|object $date
   *   A date/time object.
   *
   * @return array
   *   The formatted date/time string using the past or future format setting.
   */
  protected function formatDate(DrupalDateTime $date) {
    return parent::formatTimestamp($date->getTimestamp());
  }

}

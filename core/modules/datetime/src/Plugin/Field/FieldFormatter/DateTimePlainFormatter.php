<?php

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Plugin implementation of the 'Plain' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "datetime_plain",
 *   label = @Translation("Plain"),
 *   field_types = {
 *     "datetime"
 *   }
 *)
 */
class DateTimePlainFormatter extends DateTimeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;

        $elements[$delta] = $this->buildDate($date);
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format = $this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $timezone = $this->getSetting('timezone_override');
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', $format, $timezone != '' ? $timezone : NULL);
  }

}

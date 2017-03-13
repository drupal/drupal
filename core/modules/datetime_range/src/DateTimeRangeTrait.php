<?php

namespace Drupal\datetime_range;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides friendly methods for datetime range.
 */
trait DateTimeRangeTrait {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;

        if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
          $elements[$delta] = [
            'start_date' => $this->buildDateWithIsoAttribute($start_date),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $this->buildDateWithIsoAttribute($end_date),
          ];
        }
        else {
          $elements[$delta] = $this->buildDateWithIsoAttribute($start_date);

          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not be rendered in the field template.
            unset($item->_attributes);
          }
        }
      }
    }

    return $elements;
  }

}

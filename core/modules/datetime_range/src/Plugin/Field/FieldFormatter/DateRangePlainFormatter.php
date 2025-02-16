<?php

namespace Drupal\datetime_range\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimePlainFormatter;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'Plain' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as a plain text string, with a
 * configurable separator using an ISO-like date format string.
 */
#[FieldFormatter(
  id: 'daterange_plain',
  label: new TranslatableMarkup('Plain'),
  field_types: [
    'daterange',
  ],
)]
class DateRangePlainFormatter extends DateTimePlainFormatter {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return static::dateTimeRangeDefaultSettings() + parent::defaultSettings();
  }

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
          $elements[$delta] = $this->renderStartEnd($start_date, $separator, $end_date);
        }
        else {
          $elements[$delta] = $this->buildDate($start_date);

          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not be rendered in the field
            // template.
            unset($item->_attributes);
          }
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form = $this->dateTimeRangeSettingsForm($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array_merge(parent::settingsSummary(), $this->dateTimeRangeSettingsSummary());
  }

}

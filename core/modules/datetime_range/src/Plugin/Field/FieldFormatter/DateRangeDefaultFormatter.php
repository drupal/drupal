<?php

namespace Drupal\datetime_range\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'Default' formatter for 'daterange' fields.
 *
 * This formatter renders the data range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "daterange_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeDefaultFormatter extends DateTimeDefaultFormatter {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '-',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }

    return $summary;
  }

}

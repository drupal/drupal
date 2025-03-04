<?php

namespace Drupal\datetime_range;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides friendly methods for datetime range.
 */
trait DateTimeRangeTrait {

  /**
   * Get the default settings for a date and time range display.
   *
   * @return array
   *   An array containing default settings.
   */
  protected static function dateTimeRangeDefaultSettings(): array {
    return [
      'from_to' => DateTimeRangeDisplayOptions::Both->value,
      'separator' => '-',
    ];
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
          $elements[$delta] = $this->renderStartEndWithIsoAttribute($start_date, $separator, $end_date);
        }
        else {
          $elements[$delta] = $this->buildDateWithIsoAttribute($start_date);

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
   * Configuration form for date time range.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   Modified form array.
   */
  protected function dateTimeRangeSettingsForm(array $form): array {
    $form['from_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#options' => $this->getFromToOptions(),
      '#default_value' => $this->getSetting('from_to'),
    ];

    $field_name = $this->fieldDefinition->getName();
    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][from_to]"]' => ['value' => DateTimeRangeDisplayOptions::Both->value],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Gets the date time range settings summary.
   *
   * @return array
   *   An array of summary messages.
   */
  protected function dateTimeRangeSettingsSummary(): array {
    $summary = [];
    if ($from_to = $this->getSetting('from_to')) {
      $from_to_options = $this->getFromToOptions();
      if (isset($from_to_options[$from_to])) {
        $summary[] = $from_to_options[$from_to];
      }
    }

    if (($separator = $this->getSetting('separator')) && $this->getSetting('from_to') === DateTimeRangeDisplayOptions::Both->value) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }

    return $summary;
  }

  /**
   * Returns a list of possible values for the 'from_to' setting.
   *
   * @return array
   *   A list of 'from_to' options.
   */
  protected function getFromToOptions(): array {
    return [
      DateTimeRangeDisplayOptions::Both->value => $this->t('Display both start and end dates'),
      DateTimeRangeDisplayOptions::StartDate->value => $this->t('Display start date only'),
      DateTimeRangeDisplayOptions::EndDate->value => $this->t('Display end date only'),
    ];
  }

  /**
   * Gets whether the start date should be displayed.
   *
   * @return bool
   *   True if the start date should be displayed. False otherwise.
   */
  protected function startDateIsDisplayed(): bool {
    switch ($this->getSetting('from_to')) {
      case DateTimeRangeDisplayOptions::Both->value:
      case DateTimeRangeDisplayOptions::StartDate->value:
        return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets whether the end date should be displayed.
   *
   * @return bool
   *   True if the end date should be displayed. False otherwise.
   */
  protected function endDateIsDisplayed(): bool {
    switch ($this->getSetting('from_to')) {
      case DateTimeRangeDisplayOptions::Both->value:
      case DateTimeRangeDisplayOptions::EndDate->value:
        return TRUE;
    }

    return FALSE;
  }

  /**
   * Creates a render array given start/end dates.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date to be rendered.
   * @param string $separator
   *   The separator string.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date to be rendered.
   *
   * @return array
   *   A renderable array for a single date time range.
   */
  protected function renderStartEnd(DrupalDateTime $start_date, string $separator, DrupalDateTime $end_date): array {
    $element = [];
    if ($this->startDateIsDisplayed()) {
      $element[DateTimeRangeDisplayOptions::StartDate->value] = $this->buildDate($start_date);
    }
    if ($this->startDateIsDisplayed() && $this->endDateIsDisplayed()) {
      $element['separator'] = ['#plain_text' => ' ' . $separator . ' '];
    }
    if ($this->endDateIsDisplayed()) {
      $element[DateTimeRangeDisplayOptions::EndDate->value] = $this->buildDate($end_date);
    }
    return $element;
  }

  /**
   * Creates a render array with ISO attributes given start/end dates.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date to be rendered.
   * @param string $separator
   *   The separator string.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date to be rendered.
   *
   * @return array
   *   A renderable array for a single date time range.
   */
  protected function renderStartEndWithIsoAttribute(DrupalDateTime $start_date, string $separator, DrupalDateTime $end_date): array {
    $element = [];
    if ($this->startDateIsDisplayed()) {
      $element[DateTimeRangeDisplayOptions::StartDate->value] = $this->buildDateWithIsoAttribute($start_date);
    }
    if ($this->startDateIsDisplayed() && $this->endDateIsDisplayed()) {
      $element['separator'] = ['#plain_text' => ' ' . $separator . ' '];
    }
    if ($this->endDateIsDisplayed()) {
      $element[DateTimeRangeDisplayOptions::EndDate->value] = $this->buildDateWithIsoAttribute($end_date);
    }
    return $element;
  }

}

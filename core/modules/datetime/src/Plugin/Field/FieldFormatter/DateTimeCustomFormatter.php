<?php

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Plugin implementation of the 'Custom' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "datetime_custom",
 *   label = @Translation("Custom"),
 *   field_types = {
 *     "datetime"
 *   }
 *)
 */
class DateTimeCustomFormatter extends DateTimeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'date_format' => DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo Evaluate removing this method in
    // https://www.drupal.org/node/2793143 to determine if the behavior and
    // markup in the base class implementation can be used instead.
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
    $format = $this->getSetting('date_format');
    $timezone = $this->getSetting('timezone_override');
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', $format, $timezone != '' ? $timezone : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time format'),
      '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('date_format'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date = new DrupalDateTime();
    $this->setTimeZone($date);
    $summary[] = $date->format($this->getSetting('date_format'), $this->getFormatSettings());

    return $summary;
  }

}

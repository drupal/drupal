<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter.
 */

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
    return array(
      'date_format' => DATETIME_DATETIME_STORAGE_FORMAT,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $output = '';
      if (!empty($item->date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;

        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        $this->setTimeZone($date);

        $output = $this->formatDate($date);
      }
      $elements[$delta] = [
        '#markup' => $output,
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
      ];
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

    $form['date_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Date/time format'),
      '#description' => $this->t('See <a href="@url" target="_blank">the documentation for PHP date formats</a>.', ['@url' => 'http://php.net/manual/function.date.php']),
      '#default_value' => $this->getSetting('date_format'),
    );

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

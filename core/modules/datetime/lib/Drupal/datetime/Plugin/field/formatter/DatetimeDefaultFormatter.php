<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\field\formatter\DateTimeDefaultFormatter.
 */

namespace Drupal\datetime\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the 'datetime_default' formatter.
 *
 * @FieldFormatter(
 *   id = "datetime_default",
 *   module = "datetime",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "datetime"
 *   },
 *   settings = {
 *     "format_type" = "medium",
 *   }
 * )
 */
class DateTimeDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {

    $elements = array();

    foreach ($items as $delta => $item) {

      $formatted_date = '';
      $iso_date = '';

      if (!empty($item['date'])) {
        // The date was created and verified during field_load(), so it is safe
        // to use without further inspection.
        $date = $item['date'];

        // Create the ISO date in Universal Time.
        $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

        // The formatted output will be in local time.
        $date->setTimeZone(timezone_open(drupal_get_user_timezone()));
        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        $formatted_date = $this->dateFormat($date);
      }

      // Display the date using theme datetime.
      // @todo How should RDFa attributes be added to this?
      $elements[$delta] = array(
        '#theme' => 'datetime',
        '#text' => $formatted_date,
        '#html' => FALSE,
        '#attributes' => array(
          'datetime' => $iso_date,
          'property' => array('dc:date'),
          'datatype' => 'xsd:dateTime',
        ),
      );
    }

    return $elements;

  }

  /**
   * Creates a formatted date value as a string.
   *
   * @param object $date
   *   A date object.
   *
   * @return string
   *   A formatted date string using the chosen format.
   */
  function dateFormat($date) {
    $format_type = $this->getSetting('format_type');
    return format_date($date->getTimestamp(), $format_type);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {

    $element = array();

    $time = new DrupalDateTime();
    $format_types = system_get_date_formats();
    if (!empty($format_types)) {
      foreach ($format_types as $type => $type_info) {
        $options[$type] = $type_info['name'] . ' (' . format_date($time->format('U'), $type) . ')';
      }
    }

    $elements['format_type'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $date = new DrupalDateTime();
    $summary[] = t('Format: @display', array('@display' => $this->dateFormat($date, FALSE)));
    return $summary;
  }

}

<?php

namespace Drupal\Core\Datetime\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a datelist element.
 *
 * @FormElement("datelist")
 */
class Datelist extends DateElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#element_validate' => [
        [$class, 'validateDatelist'],
      ],
      '#process' => [
        [$class, 'processDatelist'],
      ],
      '#theme' => 'datetime_form',
      '#theme_wrappers' => ['datetime_wrapper'],
      '#date_part_order' => ['year', 'month', 'day', 'hour', 'minute'],
      '#date_year_range' => '1900:2050',
      '#date_increment' => 1,
      '#date_date_callbacks' => [],
      '#date_timezone' => date_default_timezone_get(),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Validates the date type to adjust 12 hour time and prevent invalid dates.
   * If the date is valid, the date is set in the form.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $parts = $element['#date_part_order'];
    $increment = $element['#date_increment'];

    $date = NULL;
    if ($input !== FALSE) {
      $return = $input;
      if (empty(static::checkEmptyInputs($input, $parts))) {
        if (isset($input['ampm'])) {
          if ($input['ampm'] == 'pm' && $input['hour'] < 12) {
            $input['hour'] += 12;
          }
          elseif ($input['ampm'] == 'am' && $input['hour'] == 12) {
            $input['hour'] -= 12;
          }
          unset($input['ampm']);
        }
        try {
          $date = DrupalDateTime::createFromArray($input, $element['#date_timezone']);
        }
        catch (\Exception $e) {
          $form_state->setError($element, t('Selected combination of day and month is not valid.'));
        }
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          static::incrementRound($date, $increment);
        }
      }
    }
    else {
      $return = array_fill_keys($parts, '');
      if (!empty($element['#default_value'])) {
        $date = $element['#default_value'];
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          $date->setTimezone(new \DateTimeZone($element['#date_timezone']));
          static::incrementRound($date, $increment);
          foreach ($parts as $part) {
            switch ($part) {
              case 'day':
                $format = 'j';
                break;

              case 'month':
                $format = 'n';
                break;

              case 'year':
                $format = 'Y';
                break;

              case 'hour':
                $format = in_array('ampm', $element['#date_part_order']) ? 'g' : 'G';
                break;

              case 'minute':
                $format = 'i';
                break;

              case 'second':
                $format = 's';
                break;

              case 'ampm':
                $format = 'a';
                break;

              default:
                $format = '';

            }
            $return[$part] = $date->format($format);
          }
        }
      }
    }
    $return['object'] = $date;
    return $return;
  }

  /**
   * Expands a date element into an array of individual elements.
   *
   * Required settings:
   *   - #default_value: A DrupalDateTime object, adjusted to the proper local
   *     timezone. Converting a date stored in the database from UTC to the local
   *     zone and converting it back to UTC before storing it is not handled here.
   *     This element accepts a date as the default value, and then converts the
   *     user input strings back into a new date object on submission. No timezone
   *     adjustment is performed.
   * Optional properties include:
   *   - #date_part_order: Array of date parts indicating the parts and order
   *     that should be used in the selector, optionally including 'ampm' for
   *     12 hour time. Default is array('year', 'month', 'day', 'hour', 'minute').
   *   - #date_text_parts: Array of date parts that should be presented as
   *     text fields instead of drop-down selectors. Default is an empty array.
   *   - #date_date_callbacks: Array of optional callbacks for the date element.
   *   - #date_year_range: A description of the range of years to allow, like
   *     '1900:2050', '-3:+3' or '2000:+3', where the first value describes the
   *     earliest year and the second the latest year in the range. A year
   *     in either position means that specific year. A +/- value describes a
   *     dynamic value that is that many years earlier or later than the current
   *     year at the time the form is displayed. Defaults to '1900:2050'.
   *   - #date_increment: The increment to use for minutes and seconds, i.e.
   *     '15' would show only :00, :15, :30 and :45. Defaults to 1 to show every
   *     minute.
   *   - #date_timezone: The Time Zone Identifier (TZID) to use when displaying
   *     or interpreting dates, i.e: 'Asia/Kolkata'. Defaults to the value
   *     returned by date_default_timezone_get().
   *
   * Example usage:
   * @code
   *   $form = array(
   *     '#type' => 'datelist',
   *     '#default_value' => new DrupalDateTime('2000-01-01 00:00:00'),
   *     '#date_part_order' => array('month', 'day', 'year', 'hour', 'minute', 'ampm'),
   *     '#date_text_parts' => array('year'),
   *     '#date_year_range' => '2010:2020',
   *     '#date_increment' => 15,
   *     '#date_timezone' => 'Asia/Kolkata'
   *   );
   * @endcode
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   */
  public static function processDatelist(&$element, FormStateInterface $form_state, &$complete_form) {
    // Load translated date part labels from the appropriate calendar plugin.
    $date_helper = new DateHelper();

    // The value callback has populated the #value array.
    $date = !empty($element['#value']['object']) ? $element['#value']['object'] : NULL;

    $element['#tree'] = TRUE;

    // Determine the order of the date elements.
    $order = !empty($element['#date_part_order']) ? $element['#date_part_order'] : ['year', 'month', 'day'];
    $text_parts = !empty($element['#date_text_parts']) ? $element['#date_text_parts'] : [];

    // Output multi-selector for date.
    foreach ($order as $part) {
      switch ($part) {
        case 'day':
          $options = $date_helper->days($element['#required']);
          $format = 'j';
          $title = t('Day');
          break;

        case 'month':
          $options = $date_helper->monthNamesAbbr($element['#required']);
          $format = 'n';
          $title = t('Month');
          break;

        case 'year':
          $range = static::datetimeRangeYears($element['#date_year_range'], $date);
          $options = $date_helper->years($range[0], $range[1], $element['#required']);
          $format = 'Y';
          $title = t('Year');
          break;

        case 'hour':
          $format = in_array('ampm', $element['#date_part_order']) ? 'g' : 'G';
          $options = $date_helper->hours($format, $element['#required']);
          $title = t('Hour');
          break;

        case 'minute':
          $format = 'i';
          $options = $date_helper->minutes($format, $element['#required'], $element['#date_increment']);
          $title = t('Minute');
          break;

        case 'second':
          $format = 's';
          $options = $date_helper->seconds($format, $element['#required'], $element['#date_increment']);
          $title = t('Second');
          break;

        case 'ampm':
          $format = 'a';
          $options = $date_helper->ampm($element['#required']);
          $title = t('AM/PM');
          break;

        default:
          $format = '';
          $options = [];
          $title = '';
      }

      $default = isset($element['#value'][$part]) && trim($element['#value'][$part]) != '' ? $element['#value'][$part] : '';
      $value = $date instanceof DrupalDateTime && !$date->hasErrors() ? $date->format($format) : $default;
      if (!empty($value) && $part != 'ampm') {
        $value = intval($value);
      }

      $element['#attributes']['title'] = $title;
      $element[$part] = [
        '#type' => in_array($part, $text_parts) ? 'textfield' : 'select',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#value' => $value,
        '#attributes' => $element['#attributes'],
        '#options' => $options,
        '#required' => $element['#required'],
        '#error_no_message' => FALSE,
        '#empty_option' => $title,
      ];
    }

    // Allows custom callbacks to alter the element.
    if (!empty($element['#date_date_callbacks'])) {
      foreach ($element['#date_date_callbacks'] as $callback) {
        if (function_exists($callback)) {
          $callback($element, $form_state, $date);
        }
      }
    }

    return $element;
  }

  /**
   * Validation callback for a datelist element.
   *
   * If the date is valid, the date object created from the user input is set in
   * the form for use by the caller. The work of compiling the user input back
   * into a date object is handled by the value callback, so we can use it here.
   * We also have the raw input available for validation testing.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateDatelist(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    $title = static::getElementTitle($element, $complete_form);

    if ($input_exists) {
      $all_empty = static::checkEmptyInputs($input, $element['#date_part_order']);

      // If there's empty input and the field is not required, set it to empty.
      if (empty($input['year']) && empty($input['month']) && empty($input['day']) && !$element['#required']) {
        $form_state->setValueForElement($element, NULL);
      }
      // If there's empty input and the field is required, set an error.
      elseif (empty($input['year']) && empty($input['month']) && empty($input['day']) && $element['#required']) {
        $form_state->setError($element, t('The %field date is required.', ['%field' => $title]));
      }
      elseif (!empty($all_empty)) {
        foreach ($all_empty as $value) {
          $form_state->setError($element, t('The %field date is incomplete.', ['%field' => $title]));
          $form_state->setError($element[$value], t('A value must be selected for %part.', ['%part' => $value]));
        }
      }
      else {
        // If the input is valid, set it.
        $date = $input['object'];
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          $form_state->setValueForElement($element, $date);
        }
        // If the input is invalid and an error doesn't exist, set one.
        elseif ($form_state->getError($element) === NULL) {
          $form_state->setError($element, t('The %field date is invalid.', ['%field' => $title]));
        }
      }
    }
  }

  /**
   * Checks the input array for empty values.
   *
   * Input array keys are checked against values in the parts array. Elements
   * not in the parts array are ignored. Returns an array representing elements
   * from the input array that have no value. If no empty values are found,
   * returned array is empty.
   *
   * @param array $input
   *   Array of individual inputs to check for value.
   * @param array $parts
   *   Array to check input against, ignoring elements not in this array.
   *
   * @return array
   *   Array of keys from the input array that have no value, may be empty.
   */
  protected static function checkEmptyInputs($input, $parts) {
    // Filters out empty array values, any valid value would have a string length.
    $filtered_input = array_filter($input, 'strlen');
    return array_diff($parts, array_keys($filtered_input));
  }

  /**
   * Rounds minutes and seconds to nearest requested value.
   *
   * @param $date
   * @param $increment
   *
   * @return
   */
  protected static function incrementRound(&$date, $increment) {
    // Round minutes and seconds, if necessary.
    if ($date instanceof DrupalDateTime && $increment > 1) {
      $day = intval($date->format('j'));
      $hour = intval($date->format('H'));
      $second = intval(round(intval($date->format('s')) / $increment) * $increment);
      $minute = intval($date->format('i'));
      if ($second == 60) {
        $minute += 1;
        $second = 0;
      }
      $minute = intval(round($minute / $increment) * $increment);
      if ($minute == 60) {
        $hour += 1;
        $minute = 0;
      }
      $date->setTime($hour, $minute, $second);
      if ($hour == 24) {
        $day += 1;
        $year = $date->format('Y');
        $month = $date->format('n');
        $date->setDate($year, $month, $day);
      }
    }
    return $date;
  }

}

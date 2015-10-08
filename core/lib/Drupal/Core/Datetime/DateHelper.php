<?php
/**
 * @file
 * Contains \Drupal\Core\Datetime\DateHelper.
 *
 * Lots of helpful functions for use in massaging dates, specific to the
 * Gregorian calendar system. The values include both translated and
 * untranslated values.
 *
 * Untranslated values are useful as array keys and as css identifiers, and
 * should be listed in English.
 *
 * Translated values are useful for display to the user. All values that need
 * translation should be hard-coded and wrapped in t() so the translation system
 * will be able to process them.
 */
namespace Drupal\Core\Datetime;

/**
 * Defines Gregorian Calendar date values.
 */
class DateHelper {

  /**
   * Constructs an untranslated array of month names.
   *
   * @return array
   *   An array of month names.
   */
  public static function monthNamesUntranslated() {
    // Force the key to use the correct month value, rather than
    // starting with zero.
    return array(
      1  => 'January',
      2  => 'February',
      3  => 'March',
      4  => 'April',
      5  => 'May',
      6  => 'June',
      7  => 'July',
      8  => 'August',
      9  => 'September',
      10 => 'October',
      11 => 'November',
      12 => 'December',
    );
  }

  /**
   * Constructs an untranslated array of abbreviated month names.
   *
   * @return array
   *   An array of month names.
   */
  public static function monthNamesAbbrUntranslated() {
    // Force the key to use the correct month value, rather than
    // starting with zero.
    return array(
      1  => 'Jan',
      2  => 'Feb',
      3  => 'Mar',
      4  => 'Apr',
      5  => 'May',
      6  => 'Jun',
      7  => 'Jul',
      8  => 'Aug',
      9  => 'Sep',
      10 => 'Oct',
      11 => 'Nov',
      12 => 'Dec',
    );
  }

  /**
   * Returns a translated array of month names.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of month names.
   */
  public static function monthNames($required = FALSE) {
    // Force the key to use the correct month value, rather than
    // starting with zero.
    $monthnames = array(
      1  => t('January', array(), array('context' => 'Long month name')),
      2  => t('February', array(), array('context' => 'Long month name')),
      3  => t('March', array(), array('context' => 'Long month name')),
      4  => t('April', array(), array('context' => 'Long month name')),
      5  => t('May', array(), array('context' => 'Long month name')),
      6  => t('June', array(), array('context' => 'Long month name')),
      7  => t('July', array(), array('context' => 'Long month name')),
      8  => t('August', array(), array('context' => 'Long month name')),
      9  => t('September', array(), array('context' => 'Long month name')),
      10 => t('October', array(), array('context' => 'Long month name')),
      11 => t('November', array(), array('context' => 'Long month name')),
      12 => t('December', array(), array('context' => 'Long month name')),
    );
    $none = array('' => '');
    return !$required ? $none + $monthnames : $monthnames;
  }

  /**
   * Constructs a translated array of month name abbreviations
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of month abbreviations.
   */
  public static function monthNamesAbbr($required = FALSE) {
    // Force the key to use the correct month value, rather than
    // starting with zero.
    $monthnames = array(
      1  => t('Jan', array(), array('context' => 'Abbreviated month name')),
      2  => t('Feb', array(), array('context' => 'Abbreviated month name')),
      3  => t('Mar', array(), array('context' => 'Abbreviated month name')),
      4  => t('Apr', array(), array('context' => 'Abbreviated month name')),
      5  => t('May', array(), array('context' => 'Abbreviated month name')),
      6  => t('Jun', array(), array('context' => 'Abbreviated month name')),
      7  => t('Jul', array(), array('context' => 'Abbreviated month name')),
      8  => t('Aug', array(), array('context' => 'Abbreviated month name')),
      9  => t('Sep', array(), array('context' => 'Abbreviated month name')),
      10 => t('Oct', array(), array('context' => 'Abbreviated month name')),
      11 => t('Nov', array(), array('context' => 'Abbreviated month name')),
      12 => t('Dec', array(), array('context' => 'Abbreviated month name')),
    );
    $none = array('' => '');
    return !$required ? $none + $monthnames : $monthnames;
  }

  /**
   * Constructs an untranslated array of week days.
   *
   * @return array
   *   An array of week day names
   */
  public static function weekDaysUntranslated() {
    return array(
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
    );
  }

  /**
   * Returns a translated array of week names.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of week day names
   */
  public static function weekDays($required = FALSE) {
    $weekdays = array(
      t('Sunday'),
      t('Monday'),
      t('Tuesday'),
      t('Wednesday'),
      t('Thursday'),
      t('Friday'),
      t('Saturday'),
    );
    $none = array('' => '');
    return !$required ? $none + $weekdays : $weekdays;
  }

  /**
   * Constructs a translated array of week day abbreviations.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of week day abbreviations
   */
  public static function weekDaysAbbr($required = FALSE) {
    $weekdays = array(
      t('Sun', array(), array('context' => 'Abbreviated weekday')),
      t('Mon', array(), array('context' => 'Abbreviated weekday')),
      t('Tue', array(), array('context' => 'Abbreviated weekday')),
      t('Wed', array(), array('context' => 'Abbreviated weekday')),
      t('Thu', array(), array('context' => 'Abbreviated weekday')),
      t('Fri', array(), array('context' => 'Abbreviated weekday')),
      t('Sat', array(), array('context' => 'Abbreviated weekday')),
    );
    $none = array('' => '');
    return !$required ? $none + $weekdays : $weekdays;
  }

  /**
   * Constructs a translated array of 2-letter week day abbreviations.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of week day 2 letter abbreviations
   */
  public static function weekDaysAbbr2($required = FALSE) {
    $weekdays = array(
      t('Su', array(), array('context' => 'Abbreviated weekday')),
      t('Mo', array(), array('context' => 'Abbreviated weekday')),
      t('Tu', array(), array('context' => 'Abbreviated weekday')),
      t('We', array(), array('context' => 'Abbreviated weekday')),
      t('Th', array(), array('context' => 'Abbreviated weekday')),
      t('Fr', array(), array('context' => 'Abbreviated weekday')),
      t('Sa', array(), array('context' => 'Abbreviated weekday')),
    );
    $none = array('' => '');
    return !$required ? $none + $weekdays : $weekdays;
  }

  /**
   * Constructs a translated array of 1-letter week day abbreviations.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of week day 1 letter abbreviations
   */
  public static function weekDaysAbbr1($required = FALSE) {
    $weekdays = array(
      t('S', array(), array('context' => 'Abbreviated 1 letter weekday Sunday')),
      t('M', array(), array('context' => 'Abbreviated 1 letter weekday Monday')),
      t('T', array(), array('context' => 'Abbreviated 1 letter weekday Tuesday')),
      t('W', array(), array('context' => 'Abbreviated 1 letter weekday Wednesday')),
      t('T', array(), array('context' => 'Abbreviated 1 letter weekday Thursday')),
      t('F', array(), array('context' => 'Abbreviated 1 letter weekday Friday')),
      t('S', array(), array('context' => 'Abbreviated 1 letter weekday Saturday')),
    );
    $none = array('' => '');
    return !$required ? $none + $weekdays : $weekdays;
  }

  /**
   * Reorders weekdays to match the first day of the week.
   *
   * @param array $weekdays
   *   An array of weekdays.
   *
   * @return array
   *   An array of weekdays reordered to match the first day of the week.
   */
  public static function weekDaysOrdered($weekdays) {
    $first_day = \Drupal::config('system.date')->get('first_day');
    if ($first_day > 0) {
      for ($i = 1; $i <= $first_day; $i++) {
        $last = array_shift($weekdays);
        array_push($weekdays, $last);
      }
    }
    return $weekdays;
  }

  /**
   * Constructs an array of years in a specified range.
   *
   * @param int $min
   *   (optional) The minimum year in the array. Defaults to zero.
   * @param int $max
   *   (optional) The maximum year in the array. Defaults to zero.
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of years in the selected range.
   */
  public static function years($min = 0, $max = 0, $required = FALSE) {
    // Ensure $min and $max are valid values.
    if (empty($min)) {
      $min = intval(date('Y', REQUEST_TIME) - 3);
    }
    if (empty($max)) {
      $max = intval(date('Y', REQUEST_TIME) + 3);
    }
    $none = array('' => '');
    $range = range($min, $max);
    $range = array_combine($range, $range);
    return !$required ? $none + $range : $range;
  }

  /**
   * Constructs an array of days in a month.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   * @param int $month
   *   (optional) The month in which to find the number of days. Defaults to
   *   NULL.
   * @param int $year
   *   (optional) The year in which to find the number of days. Defaults to
   *   NULL.
   *
   * @return array
   *   An array of days for the selected month.
   */
  public static function days($required = FALSE, $month = NULL, $year = NULL) {
    // If we have a month and year, find the right last day of the month.
    if (!empty($month) && !empty($year)) {
      $date = new DrupalDateTime($year . '-' . $month . '-01 00:00:00', 'UTC');
      $max = $date->format('t');
    }
    // If there is no month and year given, default to 31.
    if (empty($max)) {
      $max = 31;
    }
    $none = array('' => '');
    $range = range(1, $max);
    $range = array_combine($range, $range);
    return !$required ? $none + $range : $range;
  }


  /**
   * Constructs an array of hours.
   *
   * @param string $format
   *   (optional) A date format string that indicates the format to use for the
   *   hours. Defaults to 'H'.
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of hours in the selected format.
   */
  public static function hours($format = 'H', $required = FALSE) {
    $hours = array();
    if ($format == 'h' || $format == 'g') {
      $min = 1;
      $max = 12;
    }
    else {
      $min = 0;
      $max = 23;
    }
    for ($i = $min; $i <= $max; $i++) {
      $formatted = ($format == 'H' || $format == 'h') ? DrupalDateTime::datePad($i) : $i;
      $hours[$i] = $formatted;
    }
    $none = array('' => '');
    return !$required ? $none + $hours : $hours;
  }

  /**
   * Constructs an array of minutes.
   *
   * @param string $format
   *   (optional) A date format string that indicates the format to use for the
   *    minutes. Defaults to 'i'.
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   * @param int $increment
   *   An integer value to increment the values. Defaults to 1.
   *
   * @return array
   *   An array of minutes in the selected format.
   */
  public static function minutes($format = 'i', $required = FALSE, $increment = 1) {
    $minutes = array();
    // Ensure $increment has a value so we don't loop endlessly.
    if (empty($increment)) {
      $increment = 1;
    }
    for ($i = 0; $i < 60; $i += $increment) {
      $formatted = $format == 'i' ? DrupalDateTime::datePad($i) : $i;
      $minutes[$i] = $formatted;
    }
    $none = array('' => '');
    return !$required ? $none + $minutes : $minutes;
  }

  /**
   * Constructs an array of seconds.
   *
   * @param string $format
   *   (optional) A date format string that indicates the format to use for the
   *   seconds. Defaults to 's'.
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   * @param int $increment
   *   An integer value to increment the values. Defaults to 1.
   *
   * @return array
   *   An array of seconds in the selected format.
   */
  public static function seconds($format = 's', $required = FALSE, $increment = 1) {
    $seconds = array();
    // Ensure $increment has a value so we don't loop endlessly.
    if (empty($increment)) {
      $increment = 1;
    }
    for ($i = 0; $i < 60; $i += $increment) {
      $formatted = $format == 's' ? DrupalDateTime::datePad($i) : $i;
      $seconds[$i] = $formatted;
    }
    $none = array('' => '');
    return !$required ? $none + $seconds : $seconds;
  }

  /**
   * Constructs an array of AM and PM options.
   *
   * @param bool $required
   *   (optional) If FALSE, the returned array will include a blank value.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of AM and PM options.
   */
  public static function ampm($required = FALSE) {
    $none = array('' => '');
    $ampm = array(
             'am' => t('am', array(), array('context' => 'ampm')),
             'pm' => t('pm', array(), array('context' => 'ampm')),
            );
    return !$required ? $none + $ampm : $ampm;
  }

  /**
   * Identifies the number of days in a month for a date.
   *
   * @param mixed $date
   *   (optional) A date object, timestamp, or a date string.
   *   Defaults to NULL, which means to use the current date.
   *
   * @return int
   *   The number of days in the month.
   */
  public static function daysInMonth($date = NULL) {
    if (!$date instanceOf DrupalDateTime) {
      $date = new DrupalDateTime($date);
    }
    if (!$date->hasErrors()) {
      return $date->format('t');
    }
    return NULL;
  }

  /**
   * Identifies the number of days in a year for a date.
   *
   * @param mixed $date
   *   (optional) A date object, timestamp, or a date string.
   *   Defaults to NULL, which means to use the current date.
   *
   * @return int
   *   The number of days in the year.
   */
  public static function daysInYear($date = NULL) {
    if (!$date instanceOf DrupalDateTime) {
      $date = new DrupalDateTime($date);
    }
    if (!$date->hasErrors()) {
      if ($date->format('L')) {
        return 366;
      }
      else {
        return 365;
      }
    }
    return NULL;
  }

  /**
   * Returns day of week for a given date (0 = Sunday).
   *
   * @param mixed $date
   *   (optional) A date object, timestamp, or a date string.
   *   Defaults to NULL, which means use the current date.
   *
   * @return int
   *   The number of the day in the week.
   */
  public static function dayOfWeek($date = NULL) {
    if (!$date instanceOf DrupalDateTime) {
      $date = new DrupalDateTime($date);
    }
    if (!$date->hasErrors()) {
      return $date->format('w');
    }
    return NULL;
  }

  /**
   * Returns translated name of the day of week for a given date.
   *
   * @param mixed $date
   *   (optional) A date object, timestamp, or a date string.
   *   Defaults to NULL, which means use the current date.
   * @param string $abbr
   *   (optional) Whether to return the abbreviated name for that day.
   *   Defaults to TRUE.
   *
   * @return string
   *   The name of the day in the week for that date.
   */
  public static function dayOfWeekName($date = NULL, $abbr = TRUE) {
    if (!$date instanceOf DrupalDateTime) {
      $date = new DrupalDateTime($date);
    }
    $dow = self::dayOfWeek($date);
    $days = $abbr ? self::weekDaysAbbr() : self::weekDays();
    return $days[$dow];
  }

}


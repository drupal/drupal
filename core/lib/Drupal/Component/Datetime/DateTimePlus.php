<?php

/**
 * @file
 * Definition of Drupal\Component\Datetime\DateTimePlus
 */
namespace Drupal\Component\Datetime;

/**
 * Extends DateTime().
 *
 * This class extends the PHP DateTime class with more flexible initialization
 * parameters, allowing a date to be created from an existing date object,
 * a timestamp, a string with an unknown format, a string with a known
 * format, or an array of date parts. It also adds an errors array
 * and a __toString() method to the date object.
 *
 * In addition, it swaps the IntlDateFormatter into the format() method,
 * if it is available. The format() method is also extended with a settings
 * array to provide settings needed by the IntlDateFormatter. It will
 * will only be used if the class is available, a langcode, country, and
 * calendar have been set, and the format is in the right pattern, otherwise
 * the parent format() method is used in the usual way. These values can
 * either be set globally in the object and reused over and over as the date
 * is repeatedly formatted, or set specifically in the format() method
 * for the requested format.
 *
 * This class is less lenient than the parent DateTime class. It changes
 * the default behavior for handling date values like '2011-00-00'.
 * The parent class would convert that value to '2010-11-30' and report
 * a warning but not an error. This extension treats that as an error.
 *
 * As with the base class, a date object may be created even if it has
 * errors. It has an errors array attached to it that explains what the
 * errors are. This is less disruptive than allowing datetime exceptions
 * to abort processing. The calling script can decide what to do about
 * errors using hasErrors() and getErrors().
 */
class DateTimePlus extends \DateTime {

  const FORMAT   = 'Y-m-d H:i:s';
  const CALENDAR = 'gregorian';
  const PHP      = 'php';
  const INTL     = 'intl';

  /**
   * An array of possible date parts.
   */
  protected static $dateParts = array(
    'year',
    'month',
    'day',
    'hour',
    'minute',
    'second',
  );

  /**
   * The value of the time value passed to the constructor.
   */
  protected $inputTimeRaw = '';

  /**
   * The prepared time, without timezone, for this date.
   */
  protected $inputTimeAdjusted = '';

  /**
   * The value of the timezone passed to the constructor.
   */
  protected $inputTimeZoneRaw = '';

  /**
   * The prepared timezone object used to construct this date.
   */
  protected $inputTimeZoneAdjusted = '';

  /**
   * The value of the format passed to the constructor.
   */
  protected $inputFormatRaw = '';

  /**
   * The prepared format, if provided.
   */
  protected $inputFormatAdjusted = '';

  /**
   * The value of the language code passed to the constructor.
   */
  protected $langcode = NULL;

  /**
   * The value of the country code passed to the constructor.
   */
  protected $country = NULL;

  /**
   * The value of the calendar setting passed to the constructor.
   */
  protected $calendar = NULL;

  /**
   * An array of errors encountered when creating this date.
   */
  protected $errors = array();

  /**
   * Constructs a date object set to a requested date and timezone.
   *
   * @param mixed $time
   *   A DateTime object, a date/time string, a unix timestamp,
   *   or an array of date parts, like ('year' => 2014, 'month => 4).
   *   Defaults to 'now'.
   * @param mixed $timezone
   *   PHP DateTimeZone object, string or NULL allowed.
   *   Defaults to NULL.
   * @param string $format
   *   PHP date() type format for parsing the input. This is recommended
   *   for specialized input with a known format. If provided the
   *   date will be created using the createFromFormat() method.
   *   Defaults to NULL.
   *   @see http://us3.php.net/manual/en/datetime.createfromformat.php
   * @param array $settings
   *   - validate_format: (optional) Boolean choice to validate the
   *     created date using the input format. The format used in
   *     createFromFormat() allows slightly different values than format().
   *     Using an input format that works in both functions makes it
   *     possible to a validation step to confirm that the date created
   *     from a format string exactly matches the input. This option
   *     indicates the format can be used for validation. Defaults to TRUE.
   *   - langcode: (optional) String two letter language code to construct
   *     the locale string by the intlDateFormatter class. Used to control
   *     the result of the format() method if that class is available.
   *     Defaults to NULL.
   *   - country: (optional) String two letter country code to construct
   *     the locale string by the intlDateFormatter class. Used to control
   *     the result of the format() method if that class is available.
   *     Defaults to NULL.
   *   - calendar: (optional) String calendar name to use for the date.
   *     Defaults to DateTimePlus::CALENDAR.
   *   - debug: (optional) Boolean choice to leave debug values in the
   *     date object for debugging purposes. Defaults to FALSE.
   */
  public function __construct($time = 'now', $timezone = NULL, $format = NULL, $settings = array()) {

    // Unpack settings.
    $this->validateFormat = !empty($settings['validate_format']) ? $settings['validate_format'] : TRUE;
    $this->langcode = !empty($settings['langcode']) ? $settings['langcode'] : NULL;
    $this->country = !empty($settings['country']) ? $settings['country'] : NULL;
    $this->calendar = !empty($settings['calendar']) ? $settings['calendar'] : static::CALENDAR;

    // Store the original input so it is available for validation.
    $this->inputTimeRaw = $time;
    $this->inputTimeZoneRaw = $timezone;
    $this->inputFormatRaw = $format;

    // Massage the input values as necessary.
    $this->prepareTime($time);
    $this->prepareTimezone($timezone);
    $this->prepareFormat($format);

    // Create a date as a clone of an input DateTime object.
    if ($this->inputIsObject()) {
      $this->constructFromObject();
    }

    // Create date from array of date parts.
    elseif ($this->inputIsArray()) {
      $this->constructFromArray();
    }

    // Create a date from a Unix timestamp.
    elseif ($this->inputIsTimestamp()) {
      $this->constructFromTimestamp();
    }

    // Create a date from a time string and an expected format.
    elseif ($this->inputIsFormat()) {
      $this->constructFromFormat();
    }

    // Create a date from any other input.
    else {
      $this->constructFallback();
    }

    // Clean up the error messages.
    $this->checkErrors();
    $this->errors = array_unique($this->errors);

    // Now that we've validated the input, clean up the extra values.
    if (empty($settings['debug'])) {
      unset(
        $this->inputTimeRaw,
        $this->inputTimeAdjusted,
        $this->inputTimeZoneRaw,
        $this->inputTimeZoneAdjusted,
        $this->inputFormatRaw,
        $this->inputFormatAdjusted,
        $this->validateFormat
      );
    }

  }

  /**
   * Implements __toString() for dates.
   *
   * The base DateTime class does not implement this.
   *
   * @see https://bugs.php.net/bug.php?id=62911
   * @see http://www.serverphorums.com/read.php?7,555645
   */
  public function __toString() {
    $format = static::FORMAT;
    return $this->format($format) . ' ' . $this->getTimeZone()->getName();
  }

  /**
   * Prepares the input time value.
   *
   * Changes the input value before trying to use it, if necessary.
   * Can be overridden to handle special cases.
   *
   * @param mixed $time
   *   An input value, which could be a timestamp, a string,
   *   or an array of date parts.
   */
  protected function prepareTime($time) {
    $this->inputTimeAdjusted = $time;
  }

  /**
   * Prepares the input timezone value.
   *
   * Changes the timezone before trying to use it, if necessary.
   * Most imporantly, makes sure there is a valid timezone
   * object before moving further.
   *
   * @param mixed $timezone
   *   Either a timezone name or a timezone object or NULL.
   */
  protected function prepareTimezone($timezone) {
    // If the input timezone is a valid timezone object, use it.
    if ($timezone instanceOf \DateTimezone) {
      $timezone_adjusted = $timezone;
    }

    // When the passed-in time is a DateTime object with its own
    // timezone, try to use the date's timezone.
    elseif (empty($timezone) && $this->inputTimeAdjusted instanceOf \DateTime) {
      $timezone_adjusted = $this->inputTimeAdjusted->getTimezone();
    }

    // Allow string timezone input, and create a timezone from it.
    elseif (!empty($timezone) && is_string($timezone)) {
      $timezone_adjusted = new \DateTimeZone($timezone);
    }

    // Default to the system timezone when not explicitly provided.
    // If the system timezone is missing, use 'UTC'.
    if (empty($timezone_adjusted) || !$timezone_adjusted instanceOf \DateTimezone) {
      $system_timezone = date_default_timezone_get();
      $timezone_name = !empty($system_timezone) ? $system_timezone : 'UTC';
      $timezone_adjusted = new \DateTimeZone($timezone_name);
    }

    // We are finally certain that we have a usable timezone.
    $this->inputTimeZoneAdjusted = $timezone_adjusted;
  }

  /**
   * Prepares the input format value.
   *
   * Changes the input format before trying to use it, if necessary.
   * Can be overridden to handle special cases.
   *
   * @param string $format
   *   A PHP format string.
   */
  protected function prepareFormat($format) {
    $this->inputFormatAdjusted = $format;
  }

  /**
   * Checks whether input is a DateTime object.
   *
   * @return boolean
   *   TRUE if the input time is a DateTime object.
   */
  public function inputIsObject() {
    return $this->inputTimeAdjusted instanceOf \DateTime;
  }

  /**
   * Creates a date object from an input date object.
   */
  protected function constructFromObject() {
    try {
      $this->inputTimeAdjusted = $this->inputTimeAdjusted->format(static::FORMAT);
      parent::__construct($this->inputTimeAdjusted, $this->inputTimeZoneAdjusted);
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
  }

  /**
   * Checks whether input time seems to be a timestamp.
   *
   * Providing an input format will prevent ISO values without separators
   * from being mis-interpreted as timestamps. Providing a format can also
   * avoid interpreting a value like '2010' with a format of 'Y' as a
   * timestamp. The 'U' format indicates this is a timestamp.
   *
   * @return boolean
   *   TRUE if the input time is a timestamp.
   */
  public function inputIsTimestamp() {
    return is_numeric($this->inputTimeAdjusted) && (empty($this->inputFormatAdjusted) || $this->inputFormatAdjusted == 'U');
  }

  /**
   * Creates a date object from timestamp input.
   *
   * The timezone of a timestamp is always UTC. The timezone for a
   * timestamp indicates the timezone used by the format() method.
   */
  protected function constructFromTimestamp() {
    try {
      parent::__construct('', $this->inputTimeZoneAdjusted);
      $this->setTimestamp($this->inputTimeAdjusted);
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
  }

  /**
   * Checks if input is an array of date parts.
   *
   * @return boolean
   *   TRUE if the input time is a DateTime object.
   */
  public function inputIsArray() {
    return is_array($this->inputTimeAdjusted);
  }

  /**
   * Creates a date object from an array of date parts.
   *
   * Converts the input value into an ISO date, forcing a full ISO
   * date even if some values are missing.
   */
  protected function constructFromArray() {
    try {
      parent::__construct('', $this->inputTimeZoneAdjusted);
      $this->inputTimeAdjusted = static::prepareArray($this->inputTimeAdjusted, TRUE);
      if (static::checkArray($this->inputTimeAdjusted)) {
        // Even with validation, we can end up with a value that the
        // parent class won't handle, like a year outside the range
        // of -9999 to 9999, which will pass checkdate() but
        // fail to construct a date object.
        $this->inputTimeAdjusted = static::arrayToISO($this->inputTimeAdjusted);
        parent::__construct($this->inputTimeAdjusted, $this->inputTimeZoneAdjusted);
      }
      else {
        throw new \Exception('The array contains invalid values.');
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
  }

  /**
   * Checks if input is a string with an expected format.
   *
   * @return boolean
   *   TRUE if the input time is a string with an expected format.
   */
  public function inputIsFormat() {
    return is_string($this->inputTimeAdjusted) && !empty($this->inputFormatAdjusted);
  }

  /**
   * Creates a date object from an input format.
   */
  protected function constructFromFormat() {
    // Tries to create a date from the format and use it if possible.
    // A regular try/catch won't work right here, if the value is
    // invalid it doesn't return an exception.
    try {
      parent::__construct('', $this->inputTimeZoneAdjusted);
      $date = parent::createFromFormat($this->inputFormatAdjusted, $this->inputTimeAdjusted, $this->inputTimeZoneAdjusted);
      if (!$date instanceOf \DateTime) {
        throw new \Exception('The date cannot be created from a format.');
      }
      else {
        $this->setTimestamp($date->getTimestamp());
        $this->setTimezone($date->getTimezone());

        try {
          // The createFromFormat function is forgiving, it might
          // create a date that is not exactly a match for the provided
          // value, so test for that. For instance, an input value of
          // '11' using a format of Y (4 digits) gets created as
          // '0011' instead of '2011'.
          // Use the parent::format() because we do not want to use
          // the IntlDateFormatter here.
          if ($this->validateFormat && parent::format($this->inputFormatAdjusted) != $this->inputTimeRaw) {
            throw new \Exception('The created date does not match the input value.');
          }
        }
        catch (\Exception $e) {
          $this->errors[] = $e->getMessage();
        }
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
  }

  /**
   * Creates a date when none of the other methods are appropriate.
   *
   * Fallback construction for values that don't match any of the
   * other patterns. Lets the parent dateTime attempt to turn this string
   * into a valid date.
   */
  protected function constructFallback() {

    try {
      // One last test for invalid input before we try to construct
      // a date. If the input contains totally bogus information
      // it will blow up badly if we pass it to the constructor.
      // The date_parse() function will tell us if the input
      // makes sense.
      if (!empty($this->inputTimeAdjusted)) {
        $test = date_parse($this->inputTimeAdjusted);
        if (!empty($test['errors'])) {
          $this->errors[] = $test['errors'];
        }
      }

      if (empty($this->errors)) {
        parent::__construct($this->inputTimeAdjusted, $this->inputTimeZoneAdjusted);
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
  }

  /**
   * Examines getLastErrors() to see what errors to report.
   *
   * Two kinds of errors are important: anything that DateTime
   * considers an error, and also a warning that the date was invalid.
   * PHP creates a valid date from invalid data with only a warning,
   * 2011-02-30 becomes 2011-03-03, for instance, but we don't want that.
   *
   * @see http://us3.php.net/manual/en/time.getlasterrors.php
   */
  public function checkErrors() {
    $errors = $this->getLastErrors();
    if (!empty($errors['errors'])) {
      $this->errors += $errors['errors'];
    }
    // Most warnings are messages that the date could not be parsed
    // which causes it to be altered. For validation purposes, a warning
    // as bad as an error, because it means the constructed date does
    // not match the input value.
    if (!empty($errors['warnings'])) {
      $this->errors[] = 'The date is invalid.';
    }
  }

  /**
   * Detects if there were errors in the processing of this date.
   */
  public function hasErrors() {
    return (boolean) count($this->errors);
  }

  /**
   * Retrieves error messages.
   *
   * Public function to return the error messages.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Creates an ISO date from an array of values.
   *
   * @param array $array
   *   An array of date values keyed by date part.
   * @param bool $force_valid_date
   *   (optional) Whether to force a full date by filling in missing
   *   values. Defaults to FALSE.
   *
   * @return string
   *   The date as an ISO string.
   */
  public static function arrayToISO($array, $force_valid_date = FALSE) {
    $array = static::prepareArray($array, $force_valid_date);
    $input_time = '';
    if ($array['year'] !== '') {
      $input_time = static::datePad(intval($array['year']), 4);
      if ($force_valid_date || $array['month'] !== '') {
        $input_time .= '-' . static::datePad(intval($array['month']));
        if ($force_valid_date || $array['day'] !== '') {
          $input_time .= '-' . static::datePad(intval($array['day']));
        }
      }
    }
    if ($array['hour'] !== '') {
      $input_time .= $input_time ? 'T' : '';
      $input_time .= static::datePad(intval($array['hour']));
      if ($force_valid_date || $array['minute'] !== '') {
        $input_time .= ':' . static::datePad(intval($array['minute']));
        if ($force_valid_date || $array['second'] !== '') {
          $input_time .= ':' . static::datePad(intval($array['second']));
        }
      }
    }
    return $input_time;
  }

  /**
   * Creates a complete array from a possibly incomplete array of date parts.
   *
   * @param array $array
   *   An array of date values keyed by date part.
   * @param bool $force_valid_date
   *   (optional) Whether to force a valid date by filling in missing
   *   values with valid values or just to use empty values instead.
   *   Defaults to FALSE.
   *
   * @return array
   *   A complete array of date parts.
   */
  public static function prepareArray($array, $force_valid_date = FALSE) {
    if ($force_valid_date) {
      $now = new \DateTime();
      $array += array(
        'year'   => $now->format('Y'),
        'month'  => 1,
        'day'    => 1,
        'hour'   => 0,
        'minute' => 0,
        'second' => 0,
      );
    }
    else {
      $array += array(
        'year'   => '',
        'month'  => '',
        'day'    => '',
        'hour'   => '',
        'minute' => '',
        'second' => '',
      );
    }
    return $array;
  }

  /**
   * Checks that arrays of date parts will create a valid date.
   *
   * Checks that an array of date parts has a year, month, and day,
   * and that those values create a valid date. If time is provided,
   * verifies that the time values are valid. Sort of an
   * equivalent to checkdate().
   *
   * @param array $array
   *   An array of datetime values keyed by date part.
   *
   * @return boolean
   *   TRUE if the datetime parts contain valid values, otherwise FALSE.
   */
  public static function checkArray($array) {
    $valid_date = FALSE;
    $valid_time = TRUE;
    // Check for a valid date using checkdate(). Only values that
    // meet that test are valid.
    if (array_key_exists('year', $array) && array_key_exists('month', $array) && array_key_exists('day', $array)) {
      if (@checkdate($array['month'], $array['day'], $array['year'])) {
        $valid_date = TRUE;
      }
    }
    // Testing for valid time is reversed. Missing time is OK,
    // but incorrect values are not.
    foreach (array('hour', 'minute', 'second') as $key) {
      if (array_key_exists($key, $array)) {
        $value = $array[$key];
        switch ($value) {
          case 'hour':
            if (!preg_match('/^([1-2][0-3]|[01]?[0-9])$/', $value)) {
              $valid_time = FALSE;
            }
            break;
          case 'minute':
          case 'second':
          default:
            if (!preg_match('/^([0-5][0-9]|[0-9])$/', $value)) {
              $valid_time = FALSE;
            }
            break;
        }
      }
    }
    return $valid_date && $valid_time;
  }

  /**
   * Pads date parts with zeros.
   *
   * Helper function for a task that is often required when working with dates.
   *
   * @param int $value
   *   The value to pad.
   * @param int $size
   *   (optional) Size expected, usually 2 or 4. Defaults to 2.
   *
   * @return string
   *   The padded value.
   */
  public static function datePad($value, $size = 2) {
    return sprintf("%0" . $size . "d", $value);
  }


  /**
   * Tests whether the IntlDateFormatter can be used.
   *
   * @param string $calendar
   *   (optional) String calendar name to use for the date. Defaults to NULL.
   * @param string $langcode
   *   (optional) String two letter language code to construct the locale string
   *   by the intlDateFormatter class. Defaults to NULL.
   * @param string $country
   *   (optional) String two letter country code to construct the locale string
   *   by the intlDateFormatter class. Defaults to NULL.
   *
   * @return bool
   *   TRUE if IntlDateFormatter can be used.
   */
  public function canUseIntl($calendar = NULL, $langcode = NULL, $country = NULL) {
    $langcode = !empty($langcode) ? $langcode : $this->langcode;
    $country = !empty($country) ? $country : $this->country;
    $calendar = !empty($calendar) ? $calendar : $this->calendar;

    return class_exists('IntlDateFormatter') && !empty($calendar) && !empty($langcode) && !empty($country);
  }

  /**
   * Formats the date for display.
   *
   * Uses the IntlDateFormatter to display the format, if possible.
   * Adds an optional array of settings that provides the information
   * the IntlDateFormatter will need.
   *
   * @param string $format
   *   A format string using either PHP's date() or the
   *   IntlDateFormatter() format.
   * @param array $settings
   *   - format_string_type: (optional) DateTimePlus::PHP or
   *     DateTimePlus::INTL. Identifies the pattern used by the format
   *     string. When using the Intl formatter, the format string must
   *     use the Intl pattern, which is different from the pattern used
   *     by the DateTime format function. Defaults to DateTimePlus::PHP.
   *   - timezone: (optional) String timezone name. Defaults to the timezone
   *     of the date object.
   *   - langcode: (optional) String two letter language code to construct the
   *     locale string by the intlDateFormatter class. Used to control the
   *     result of the format() method if that class is available. Defaults
   *     to NULL.
   *   - country: (optional) String two letter country code to construct the
   *     locale string by the intlDateFormatter class. Used to control the
   *     result of the format() method if that class is available. Defaults
   *     to NULL.
   *   - calendar: (optional) String calendar name to use for the date,
   *     Defaults to DateTimePlus::CALENDAR.
   *   - date_type: (optional) Integer date type to use in the formatter,
   *     defaults to IntlDateFormatter::FULL.
   *   - time_type: (optional) Integer date type to use in the formatter,
   *     defaults to IntlDateFormatter::FULL.
   *   - lenient: (optional) Boolean choice of whether or not to use lenient
   *     processing in the intl formatter. Defaults to FALSE;
   *
   * @return string
   *   The formatted value of the date.
   */
  public function format($format, $settings = array()) {

    // If there were construction errors, we can't format the date.
    if ($this->hasErrors()) {
      return;
    }

    $format_string_type = isset($settings['format_string_type']) ? $settings['format_string_type'] : static::PHP;
    $langcode = !empty($settings['langcode']) ? $settings['langcode'] : $this->langcode;
    $country = !empty($settings['country']) ? $settings['country'] : $this->country;
    $calendar = !empty($settings['calendar']) ? $settings['calendar'] : $this->calendar;

    // Format the date and catch errors.
    try {

      // If we have what we need to use the IntlDateFormatter, do so.
      if ($this->canUseIntl($calendar, $langcode, $country) && $format_string_type == static::INTL) {

        // Construct the $locale variable needed by the IntlDateFormatter.
        $locale = $langcode . '_' . $country;

        // If we have information about a calendar, add it.
        if (!empty($calendar) && $calendar != static::CALENDAR) {
          $locale .= '@calendar=' . $calendar;
        }

        // If we're working with a non-gregorian calendar, indicate that.
        $calendar_type = \IntlDateFormatter::GREGORIAN;
        if ($calendar != self::CALENDAR) {
          $calendar_type = \IntlDateFormatter::TRADITIONAL;
        }

        $date_type = !empty($settings['date_type']) ? $settings['date_type'] : \IntlDateFormatter::FULL;
        $time_type = !empty($settings['time_type']) ? $settings['time_type'] : \IntlDateFormatter::FULL;
        $timezone = !empty($settings['timezone']) ? $settings['timezone'] : $this->getTimezone()->getName();
        $formatter = new \IntlDateFormatter($locale, $date_type, $time_type, $timezone, $calendar_type, $format);

        $lenient = !empty($settings['lenient']) ? $settings['lenient'] : FALSE;
        $formatter->setLenient($lenient);
        $value = $formatter->format($this);
      }

      // Otherwise, use the parent method.
      else {
        $value = parent::format($format);
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
    return $value;
  }
}

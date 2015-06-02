<?php

/**
 * @file
 * Definition of Drupal\Component\Datetime\DateTimePlus
 */
namespace Drupal\Component\Datetime;

/**
 * Wraps DateTime().
 *
 * This class wraps the PHP DateTime class with more flexible initialization
 * parameters, allowing a date to be created from an existing date object,
 * a timestamp, a string with an unknown format, a string with a known
 * format, or an array of date parts. It also adds an errors array
 * and a __toString() method to the date object.
 *
 * This class is less lenient than the DateTime class. It changes
 * the default behavior for handling date values like '2011-00-00'.
 * The DateTime class would convert that value to '2010-11-30' and report
 * a warning but not an error. This extension treats that as an error.
 *
 * As with the DateTime class, a date object may be created even if it has
 * errors. It has an errors array attached to it that explains what the
 * errors are. This is less disruptive than allowing datetime exceptions
 * to abort processing. The calling script can decide what to do about
 * errors using hasErrors() and getErrors().
 */
class DateTimePlus {

  const FORMAT   = 'Y-m-d H:i:s';

  /**
   * A RFC7231 Compliant date.
   *
   * http://tools.ietf.org/html/rfc7231#section-7.1.1.1
   *
   * Example: Sun, 06 Nov 1994 08:49:37 GMT
   */
  const RFC7231 = 'D, d M Y H:i:s \G\M\T';

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
   * An array of errors encountered when creating this date.
   */
  protected $errors = array();

  /**
   * The DateTime object.
   *
   * @var \DateTime
   */
  protected $dateTimeObject = NULL;

  /**
   * Creates a date object from an input date object.
   *
   * @param \DateTime $datetime
   *   A DateTime object.
   * @param array $settings
   *   @see __construct()
   */
  public static function createFromDateTime(\DateTime $datetime, $settings = array()) {
    return new static($datetime->format(static::FORMAT), $datetime->getTimezone(), $settings);
  }

  /**
   * Creates a date object from an array of date parts.
   *
   * Converts the input value into an ISO date, forcing a full ISO
   * date even if some values are missing.
   *
   * @param array $date_parts
   *   An array of date parts, like ('year' => 2014, 'month => 4).
   * @param mixed $timezone
   *   (optional) \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param array $settings
   *   (optional) A keyed array for settings, suitable for passing on to
   *   __construct().
   *
   * @return static
   *   A new \Drupal\Component\DateTimePlus object based on the parameters
   *   passed in.
   */
  public static function createFromArray(array $date_parts, $timezone = NULL, $settings = array()) {
    $date_parts = static::prepareArray($date_parts, TRUE);
    if (static::checkArray($date_parts)) {
      // Even with validation, we can end up with a value that the
      // DateTime class won't handle, like a year outside the range
      // of -9999 to 9999, which will pass checkdate() but
      // fail to construct a date object.
      $iso_date = static::arrayToISO($date_parts);
      return new static($iso_date, $timezone, $settings);
    }
    else {
      throw new \Exception('The array contains invalid values.');
    }
  }

  /**
   * Creates a date object from timestamp input.
   *
   * The timezone of a timestamp is always UTC. The timezone for a
   * timestamp indicates the timezone used by the format() method.
   *
   * @param int $timestamp
   *   A UNIX timestamp.
   * @param mixed $timezone
   *   @see __construct()
   * @param array $settings
   *   @see __construct()
   */
  public static function createFromTimestamp($timestamp, $timezone = NULL, $settings = array()) {
    if (!is_numeric($timestamp)) {
      throw new \Exception('The timestamp must be numeric.');
    }
    $datetime = new static('', $timezone, $settings);
    $datetime->setTimestamp($timestamp);
    return $datetime;
  }

  /**
   * Creates a date object from an input format.
   *
   * @param string $format
   *   PHP date() type format for parsing the input. This is recommended
   *   to use things like negative years, which php's parser fails on, or
   *   any other specialized input with a known format. If provided the
   *   date will be created using the createFromFormat() method.
   *   @see http://us3.php.net/manual/en/datetime.createfromformat.php
   * @param mixed $time
   *   @see __construct()
   * @param mixed $timezone
   *   @see __construct()
   * @param array $settings
   *   - validate_format: (optional) Boolean choice to validate the
   *     created date using the input format. The format used in
   *     createFromFormat() allows slightly different values than format().
   *     Using an input format that works in both functions makes it
   *     possible to a validation step to confirm that the date created
   *     from a format string exactly matches the input. This option
   *     indicates the format can be used for validation. Defaults to TRUE.
   *   @see __construct()
   */
  public static function createFromFormat($format, $time, $timezone = NULL, $settings = array()) {
    if (!isset($settings['validate_format'])) {
      $settings['validate_format'] = TRUE;
    }

    // Tries to create a date from the format and use it if possible.
    // A regular try/catch won't work right here, if the value is
    // invalid it doesn't return an exception.
    $datetimeplus = new static('', $timezone, $settings);

    $date = \DateTime::createFromFormat($format, $time, $datetimeplus->getTimezone());
    if (!$date instanceOf \DateTime) {
      throw new \Exception('The date cannot be created from a format.');
    }
    else {
      // Functions that parse date is forgiving, it might create a date that
      // is not exactly a match for the provided value, so test for that by
      // re-creating the date/time formatted string and comparing it to the input. For
      // instance, an input value of '11' using a format of Y (4 digits) gets
      // created as '0011' instead of '2011'.
      if ($date instanceOf DateTimePlus) {
        $test_time = $date->format($format, $settings);
      }
      elseif ($date instanceOf \DateTime) {
        $test_time = $date->format($format);
      }
      $datetimeplus->setTimestamp($date->getTimestamp());
      $datetimeplus->setTimezone($date->getTimezone());

      if ($settings['validate_format'] && $test_time != $time) {
        throw new \Exception('The created date does not match the input value.');
      }
    }
    return $datetimeplus;
  }

  /**
   * Constructs a date object set to a requested date and timezone.
   *
   * @param string $time
   *   (optional) A date/time string. Defaults to 'now'.
   * @param mixed $timezone
   *   (optional) \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param array $settings
   *   (optional) Keyed array of settings. Defaults to empty array.
   *   - langcode: (optional) String two letter language code used to control
   *     the result of the format(). Defaults to NULL.
   *   - debug: (optional) Boolean choice to leave debug values in the
   *     date object for debugging purposes. Defaults to FALSE.
   */
  public function __construct($time = 'now', $timezone = NULL, $settings = array()) {

    // Unpack settings.
    $this->langcode = !empty($settings['langcode']) ? $settings['langcode'] : NULL;

    // Massage the input values as necessary.
    $prepared_time = $this->prepareTime($time);
    $prepared_timezone = $this->prepareTimezone($timezone);

    try {
      if (!empty($prepared_time)) {
        $test = date_parse($prepared_time);
        if (!empty($test['errors'])) {
          $this->errors[] = $test['errors'];
        }
      }

      if (empty($this->errors)) {
        $this->dateTimeObject = new \DateTime($prepared_time, $prepared_timezone);
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }

    // Clean up the error messages.
    $this->checkErrors();
    $this->errors = array_unique($this->errors);
  }

  /**
   * Implements __toString() for dates.
   *
   * The DateTime class does not implement this.
   *
   * @see https://bugs.php.net/bug.php?id=62911
   * @see http://www.serverphorums.com/read.php?7,555645
   */
  public function __toString() {
    $format = static::FORMAT;
    return $this->format($format) . ' ' . $this->getTimeZone()->getName();
  }

  /**
   * Implements the magic __call method.
   *
   * Passes through all unknown calls onto the DateTime object.
   */
  public function __call($method, $args) {
    // @todo consider using assert() as per https://www.drupal.org/node/2451793.
    if (!isset($this->dateTimeObject)) {
      throw new \Exception('DateTime object not set.');
    }
    if (!method_exists($this->dateTimeObject, $method)) {
      throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_class($this), $method));
    }
    return call_user_func_array(array($this->dateTimeObject, $method), $args);
  }

  /**
   * Implements the magic __callStatic method.
   *
   * Passes through all unknown static calls onto the DateTime object.
   */
  public static function __callStatic($method, $args) {
    if (!method_exists('\DateTime', $method)) {
      throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_called_class(), $method));
    }
    return call_user_func_array(array('\DateTime', $method), $args);
  }

  /**
   * Implements the magic __clone method.
   *
   * Deep-clones the DateTime object we're wrapping.
   */
  public function __clone() {
    $this->dateTimeObject = clone($this->dateTimeObject);
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
    return $time;
  }

  /**
   * Prepares the input timezone value.
   *
   * Changes the timezone before trying to use it, if necessary.
   * Most importantly, makes sure there is a valid timezone
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
    return $timezone_adjusted;
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
    return $format;
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
    $errors = \DateTime::getLastErrors();
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
   * Gets error messages.
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
        switch ($key) {
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
   * Formats the date for display.
   *
   * @param string $format
   *   A format string using either PHP's date().
   * @param array $settings
   *   - timezone: (optional) String timezone name. Defaults to the timezone
   *     of the date object.
   *
   * @return string
   *   The formatted value of the date.
   */
  public function format($format, $settings = array()) {

    // If there were construction errors, we can't format the date.
    if ($this->hasErrors()) {
      return;
    }

    // Format the date and catch errors.
    try {
      // Clone the date/time object so we can change the time zone without
      // disturbing the value stored in the object.
      $dateTimeObject = clone $this->dateTimeObject;
      if (isset($settings['timezone'])) {
        $dateTimeObject->setTimezone(new \DateTimeZone($settings['timezone']));
      }
      $value = $dateTimeObject->format($format);
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }

    return $value;
  }
}

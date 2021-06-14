<?php

namespace Drupal\Core\Datetime;

/**
 * Provides an interface defining a date formatter.
 */
interface DateFormatterInterface {

  /**
   * Formats a date, using a date type or a custom date format string.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   * @param string $type
   *   (optional) The format to use, one of:
   *   - One of the built-in formats: 'short', 'medium',
   *     'long', 'html_datetime', 'html_date', 'html_time',
   *     'html_yearless_date', 'html_week', 'html_month', 'html_year'.
   *   - The name of a date type defined by a date format config entity.
   *   - The machine name of an administrator-defined date format.
   *   - 'custom', to use $format.
   *   Defaults to 'medium'.
   * @param string $format
   *   (optional) If $type is 'custom', a PHP date format string suitable for
   *   input to date(). Use a backslash to escape ordinary text, so it does not
   *   get interpreted as date format characters.
   * @param string|null $timezone
   *   (optional) Time zone identifier, as described at
   *   http://php.net/manual/timezones.php Defaults to the time zone used to
   *   display the page.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the user interface language for the page.
   *
   * @return string
   *   A translated date string in the requested format. Since the format may
   *   contain user input, this value should be escaped when output.
   */
  public function format($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL);

  /**
   * Formats a time interval with the requested granularity.
   *
   * Note that for intervals over 30 days, the output is approximate: a "month"
   * is always exactly 30 days, and a "year" is always 365 days. It is not
   * possible to make a more exact representation, given that there is only one
   * input in seconds. If you are formatting an interval between two specific
   * timestamps, use \Drupal\Core\Datetime\DateFormatter::formatDiff() instead.
   *
   * @param int $interval
   *   The length of the interval in seconds.
   * @param int $granularity
   *   (optional) How many different units to display in the string (2 by
   *   default).
   * @param string|null $langcode
   *   (optional) langcode: The language code for the language used to format
   *   the date. Defaults to NULL, which results in the user interface language
   *   for the page being used.
   *
   * @return string
   *   A translated string representation of the interval.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatDiff()
   */
  public function formatInterval($interval, $granularity = 2, $langcode = NULL);

  /**
   * Provides values for all date formatting characters for a given timestamp.
   *
   * @param string|null $langcode
   *   (optional) Language code of the date format, if different from the site
   *   default language.
   * @param int|null $timestamp
   *   (optional) The Unix timestamp to format, defaults to current time.
   * @param string|null $timezone
   *   (optional) The timezone to use, if different from the site's default
   *   timezone.
   *
   * @return array
   *   An array of formatted date values, indexed by the date format character.
   *
   * @see date()
   */
  public function getSampleDateFormats($langcode = NULL, $timestamp = NULL, $timezone = NULL);

  /**
   * Formats the time difference from the current request time to a timestamp.
   *
   * @param $timestamp
   *   A UNIX timestamp to compare against the current request time.
   * @param array $options
   *   (optional) An associative array with additional options. The following
   *   keys can be used:
   *   - granularity: An integer value that signals how many different units to
   *     display in the string. Defaults to 2.
   *   - langcode: The language code for the language used to format the date.
   *     Defaults to NULL, which results in the user interface language for the
   *     page being used.
   *   - strict: A Boolean value indicating whether or not the timestamp can be
   *     before the current request time. If TRUE (default) and $timestamp is
   *     before the current request time, the result string will be "0 seconds".
   *     If FALSE and $timestamp is before the current request time, the result
   *     string will be the formatted time difference.
   *   - return_as_object: A Boolean value whether to return a FormattedDateDiff
   *     object.
   *
   * @return string|\Drupal\Core\Datetime\FormattedDateDiff
   *   A translated string representation of the difference between the given
   *   timestamp and the current request time. This interval is always positive.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatDiff()
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatTimeDiffSince()
   */
  public function formatTimeDiffUntil($timestamp, $options = []);

  /**
   * Formats the time difference from a timestamp to the current request time.
   *
   * @param $timestamp
   *   A UNIX timestamp to compare against the current request time.
   * @param array $options
   *   (optional) An associative array with additional options. The following
   *   keys can be used:
   *   - granularity: An integer value that signals how many different units to
   *     display in the string. Defaults to 2.
   *   - langcode: The language code for the language used to format the date.
   *     Defaults to NULL, which results in the user interface language for the
   *     page being used.
   *   - strict: A Boolean value indicating whether or not the timestamp can be
   *     after the current request time. If TRUE (default) and $timestamp is
   *     after the current request time, the result string will be "0 seconds".
   *     If FALSE and $timestamp is after the current request time, the result
   *     string will be the formatted time difference.
   *   - return_as_object: A Boolean value whether to return a FormattedDateDiff
   *     object.
   *
   * @return string|\Drupal\Core\Datetime\FormattedDateDiff
   *   A translated string representation of the difference between the given
   *   timestamp and the current request time. This interval is always positive.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatDiff()
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatTimeDiffUntil()
   */
  public function formatTimeDiffSince($timestamp, $options = []);

  /**
   * Formats a time interval between two timestamps.
   *
   * @param int $from
   *   A UNIX timestamp, defining the from date and time.
   * @param int $to
   *   A UNIX timestamp, defining the to date and time.
   * @param array $options
   *   (optional) An associative array with additional options. The following
   *   keys can be used:
   *   - granularity: An integer value that signals how many different units to
   *     display in the string. Defaults to 2.
   *   - langcode: The language code for the language used to format the date.
   *     Defaults to NULL, which results in the user interface language for the
   *     page being used.
   *   - strict: A Boolean value indicating whether or not the $from timestamp
   *     can be after the $to timestamp. If TRUE (default) and $from is after
   *     $to, the result string will be "0 seconds". If FALSE and $from is
   *     after $to, the result string will be the formatted time difference.
   *   - return_as_object: A Boolean value whether to return a FormattedDateDiff
   *     object.
   *
   * @return string|\Drupal\Core\Datetime\FormattedDateDiff
   *   A translated string representation of the interval. This interval is
   *   always positive.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatInterval()
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatTimeDiffSince()
   * @see \Drupal\Core\Datetime\DateFormatterInterface::formatTimeDiffUntil()
   */
  public function formatDiff($from, $to, $options = []);

}

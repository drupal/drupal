<?php

/**
 * @file
 * Definition of Drupal\Core\Datetime\DrupalDateTime.
 */
namespace Drupal\Core\Datetime;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Language\Language;

/**
 * Extends DateTimePlus().
 *
 * This class extends the basic component and adds in Drupal-specific
 * handling, like translation of the format() method.
 *
 * @see \Drupal/Component/Datetime/DateTimePlus.php
 */
class DrupalDateTime extends DateTimePlus {

  /**
   * Constructs a date object.
   *
   * @param mixed $time
   *   A DateTime object, a date/input_time_adjusted string, a unix timestamp,
   *   or an array of date parts, like ('year' => 2014, 'month => 4).
   *   Defaults to 'now'.
   * @param mixed $timezone
   *   PHP DateTimeZone object, string or NULL allowed.
   *   Defaults to NULL.
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
  public function __construct($time = 'now', $timezone = NULL, $settings = array()) {
    // We can set the langcode and country using Drupal values.
    if (!isset($settings['langcode'])) {
      $settings['langcode'] = \Drupal::languageManager()->getCurrentLanguage()->id;
    }

    if (!isset($settings['country'])) {
      $settings['country'] = \Drupal::config('system.date')->get('country.default');
    }

    // Instantiate the parent class.
    parent::__construct($time, $timezone, $settings);

  }

  /**
   * Overrides prepareTimezone().
   *
   * Override basic component timezone handling to use Drupal's
   * knowledge of the preferred user timezone.
   */
  protected function prepareTimezone($timezone) {
    $user_timezone = drupal_get_user_timezone();
    if (empty($timezone) && !empty($user_timezone)) {
      $timezone = $user_timezone;
    }
    return parent::prepareTimezone($timezone);
  }

  /**
   * Overrides format().
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

    $settings['format_string_type'] = isset($settings['format_string_type']) ? $settings['format_string_type'] : static::PHP;
    $settings['calendar'] = !empty($settings['calendar']) ? $settings['calendar'] : $this->calendar;
    $settings['langcode'] = !empty($settings['langcode']) ? $settings['langcode'] : $this->langcode;
    $settings['country'] = !empty($settings['country']) ? $settings['country'] : $this->country;
    // Format the date and catch errors.
    try {

      // If we have what we need to use the IntlDateFormatter, do so.
      if ($this->canUseIntl($settings['calendar'], $settings['langcode'], $settings['country']) && $settings['format_string_type'] == parent::INTL) {
        $value = parent::format($format, $settings);
      }

      // Otherwise, use the default Drupal method.
      else {

        // Encode markers that should be translated. 'A' becomes
        // '\xEF\AA\xFF'. xEF and xFF are invalid UTF-8 sequences,
        // and we assume they are not in the input string.
        // Paired backslashes are isolated to prevent errors in
        // read-ahead evaluation. The read-ahead expression ensures that
        // A matches, but not \A.
        $format = preg_replace(array('/\\\\\\\\/', '/(?<!\\\\)([AaeDlMTF])/'), array("\xEF\\\\\\\\\xFF", "\xEF\\\\\$1\$1\xFF"), $format);

        // Call date_format().
        $format = parent::format($format);

        // Pass the langcode to _format_date_callback().
        _format_date_callback(NULL, $settings['langcode']);

        // Translate the marked sequences.
        $value = preg_replace_callback('/\xEF([AaeDlMTF]?)(.*?)\xFF/', '_format_date_callback', $format);
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
    return $value;
  }
}

<?php

/**
 * @file
 * Definition of Drupal\Core\Datetime\DrupalDateTime.
 */
namespace Drupal\Core\Datetime;

use Drupal\Component\Datetime\DateTimePlus;

/**
 * Extends DateTimePlus().
 *
 * This class extends the basic component and adds in Drupal-specific
 * handling, like translation of the format() method.
 *
 * Static methods in base class can also be used to create DrupalDateTime objects.
 * For example:
 *
 * DrupalDateTime::createFromArray( array('year' => 2010, 'month' => 9, 'day' => 28) )
 *
 * @see \Drupal/Component/Datetime/DateTimePlus.php
 */
class DrupalDateTime extends DateTimePlus {

  /**
   * Constructs a date object.
   *
   * @param string $time
   *   A DateTime object, a date/input_time_adjusted string, a unix timestamp.
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
   *   - langcode: (optional) Used to control the result of the format() method.
   *     Defaults to NULL.
   *   - debug: (optional) Boolean choice to leave debug values in the
   *     date object for debugging purposes. Defaults to FALSE.
   */
  public function __construct($time = 'now', $timezone = NULL, $settings = array()) {
    if (!isset($settings['langcode'])) {
      $settings['langcode'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
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
    if (empty($timezone)) {
      // Fallback to user or system default timezone.
      $timezone = drupal_get_user_timezone();
    }
    return parent::prepareTimezone($timezone);
  }

  /**
   * Overrides format().
   *
   * @param string $format
   *   A format string using either PHP's date().
   * @param array $settings
   *   - timezone: (optional) String timezone name. Defaults to the timezone
   *     of the date object.
   *   - langcode: (optional) String two letter language code used to control
   *     the result of the format() method. Defaults to NULL.
   *
   * @return string
   *   The formatted value of the date.
   */
  public function format($format, $settings = array()) {
    $settings['langcode'] = !empty($settings['langcode']) ? $settings['langcode'] : $this->langcode;
    // Format the date and catch errors.
    try {
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
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
    return $value;
  }
}

<?php

namespace Drupal\Core\Datetime;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
 * @see \Drupal\Component\Datetime\DateTimePlus
 */
class DrupalDateTime extends DateTimePlus {

  use StringTranslationTrait;

  /**
   * Format string translation cache.
   */
  protected $formatTranslationCache;

  /**
   * Constructs a date object.
   *
   * @param string $time
   *   A date/input_time_adjusted string. Defaults to 'now'.
   * @param mixed $timezone
   *   PHP DateTimeZone object, string or NULL allowed.
   *   Defaults to NULL. Note that the $timezone parameter and the current
   *   timezone are ignored when the $time parameter either is a UNIX timestamp
   *   (e.g. @946684800) or specifies a timezone
   *   (e.g. 2010-01-28T15:00:00+02:00).
   *   @see http://php.net/manual/datetime.construct.php
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
  public function __construct($time = 'now', $timezone = NULL, $settings = []) {
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
   *   The formatted value of the date. Since the format may contain user input,
   *   this value should be escaped when output.
   */
  public function format($format, $settings = []) {
    $langcode = !empty($settings['langcode']) ? $settings['langcode'] : $this->langcode;
    $value = '';
    // Format the date and catch errors.
    try {
      // Encode markers that should be translated. 'A' becomes
      // '\xEF\AA\xFF'. xEF and xFF are invalid UTF-8 sequences,
      // and we assume they are not in the input string.
      // Paired backslashes are isolated to prevent errors in
      // read-ahead evaluation. The read-ahead expression ensures that
      // A matches, but not \A.
      $format = preg_replace(['/\\\\\\\\/', '/(?<!\\\\)([AaeDlMTF])/'], ["\xEF\\\\\\\\\xFF", "\xEF\\\\\$1\$1\xFF"], $format);

      // Call date_format().
      $format = parent::format($format, $settings);

      // Translates a formatted date string.
      $translation_callback = function ($matches) use ($langcode) {
        $code = $matches[1];
        $string = $matches[2];
        if (!isset($this->formatTranslationCache[$langcode][$code][$string])) {
          $options = ['langcode' => $langcode];
          if ($code == 'F') {
            $options['context'] = 'Long month name';
          }

          if ($code == '') {
            $this->formatTranslationCache[$langcode][$code][$string] = $string;
          }
          else {
            $this->formatTranslationCache[$langcode][$code][$string] = $this->t($string, [], $options);
          }
        }
        return $this->formatTranslationCache[$langcode][$code][$string];
      };

      // Translate the marked sequences.
      $value = preg_replace_callback('/\xEF([AaeDlMTF]?)(.*?)\xFF/', $translation_callback, $format);
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
    return $value;
  }

}

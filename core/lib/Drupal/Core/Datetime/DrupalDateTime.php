<?php

namespace Drupal\Core\Datetime;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Extends DateTimePlus().
 *
 * This class extends the basic component and adds in Drupal-specific
 * handling, like translation of the format() method.
 *
 * Static methods in base class can also be used to create DrupalDateTime
 * objects. For example:
 *
 * @code
 * DrupalDateTime::createFromArray(['year' => 2010, 'month' => 9, 'day' => 28])
 * @endcode
 *
 * @see \Drupal\Component\Datetime\DateTimePlus
 */
class DrupalDateTime extends DateTimePlus {

  use StringTranslationTrait;
  use DependencySerializationTrait {
    __sleep as defaultSleep;
  }

  /**
   * Formatted strings translation cache.
   *
   * @var array
   * Translation cache represents an instance storage for formatted date
   * strings. It contains a multidimensional array where:
   * - first level keys - are drupal language codes;
   * - second level keys - are each symbols of given format string (like 'F');
   * - third level keys - are original matched strings related to the symbol;
   * - values - are translated or not-translated original strings (depends on
   *   if a particular symbol represents translatable value according to PHP's
   *   date() format character).
   *
   * For example:
   * @code
   *   [
   *     'en' => [
   *       'F' => [
   *         'November' => t('November'),
   *         'December' => t('December'),
   *       ],
   *       'd' => [
   *         '10' => '10',
   *         '31' => '31',
   *       ],
   *     ],
   *   ]
   * @endcode
   */
  protected $formatTranslationCache = [];

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
   *   phpcs:ignore Drupal.Commenting.FunctionComment.ParamCommentFullStop
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
      $timezone = date_default_timezone_get();
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
      $format = preg_replace(['/\\\\\\\\/', '/(?<!\\\\)([SAaeDlMTF])/'], ["\xEF\\\\\\\\\xFF", "\xEF\\\\\$1\$1\xFF"], $format);

      // Call date_format().
      $format = parent::format($format, $settings);

      // $format will be NULL if there are any errors.
      if ($format !== NULL) {
        // Translates a formatted date string.
        $translation_callback = function ($matches) use ($langcode) {
          $code = $matches[1];
          $string = $matches[2];
          if (!isset($this->formatTranslationCache[$langcode][$code][$string])) {
            $options = ['langcode' => $langcode];
            if ($code == 'F') {
              $options['context'] = 'Long month name';
            }
            if ($code == 'M') {
              $options['context'] = 'Abbreviated month name';
            }
            if ($code == 'S') {
              $options['context'] = 'Day ordinal suffix';
            }

            if ($code == '') {
              $this->formatTranslationCache[$langcode][$code][$string] = $string;
            }
            else {
              // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
              $this->formatTranslationCache[$langcode][$code][$string] = $this->t($string, [], $options);
            }
          }
          return $this->formatTranslationCache[$langcode][$code][$string];
        };

        // Translate the marked sequences.
        $value = preg_replace_callback('/\xEF([SAaeDlMTF]?)(.*?)\xFF/', $translation_callback, $format);
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep(): array {
    return array_diff($this->defaultSleep(), ['formatTranslationCache']);
  }

}

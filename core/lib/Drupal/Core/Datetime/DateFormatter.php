<?php

/**
 * @file
 * Contains \Drupal\Core\Datetime\DateFormatter.
 */

namespace Drupal\Core\Datetime;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a service to handler various date related functionality.
 *
 * @ingroup i18n
 */
class DateFormatter {
  use StringTranslationTrait;

  /**
   * The list of loaded timezones.
   *
   * @var array
   */
  protected $timezones;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Language manager for retrieving the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  protected $country = NULL;
  protected $dateFormats = array();

  /**
   * Contains the different date interval units.
   *
   * This array is keyed by strings representing the unit (e.g.
   * '1 year|@count years') and with the amount of values of the unit in
   * seconds.
   *
   * @var array
   */
  protected $units = array(
    '1 year|@count years' => 31536000,
    '1 month|@count months' => 2592000,
    '1 week|@count weeks' => 604800,
    '1 day|@count days' => 86400,
    '1 hour|@count hours' => 3600,
    '1 min|@count min' => 60,
    '1 sec|@count sec' => 1,
  );

  /**
   * Constructs a Date object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, TranslationInterface $translation, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->dateFormatStorage = $entity_manager->getStorage('date_format');
    $this->languageManager = $language_manager;
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

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
  public function format($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
    if (!isset($timezone)) {
      $timezone = date_default_timezone_get();
    }
    // Store DateTimeZone objects in an array rather than repeatedly
    // constructing identical objects over the life of a request.
    if (!isset($this->timezones[$timezone])) {
      $this->timezones[$timezone] = timezone_open($timezone);
    }

    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    // Create a DrupalDateTime object from the timestamp and timezone.
    $create_settings = array(
      'langcode' => $langcode,
      'country' => $this->country(),
    );
    $date = DrupalDateTime::createFromTimestamp($timestamp, $this->timezones[$timezone], $create_settings);

    // If we have a non-custom date format use the provided date format pattern.
    if ($date_format = $this->dateFormat($type, $langcode)) {
      $format = $date_format->getPattern();
    }

    // Fall back to medium if a format was not found.
    if (empty($format)) {
      $format = $this->dateFormat('fallback', $langcode)->getPattern();
    }

    // Call $date->format().
    $settings = array(
      'langcode' => $langcode,
    );
    return $date->format($format, $settings);
  }

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
   * @see \Drupal\Core\Datetime\DateFormatter::formatDiff()
   */
  public function formatInterval($interval, $granularity = 2, $langcode = NULL) {
    $output = '';
    foreach ($this->units as $key => $value) {
      $key = explode('|', $key);
      if ($interval >= $value) {
        $output .= ($output ? ' ' : '') . $this->formatPlural(floor($interval / $value), $key[0], $key[1], array(), array('langcode' => $langcode));
        $interval %= $value;
        $granularity--;
      }
      elseif ($output) {
        // Break if there was previous output but not any output at this level,
        // to avoid skipping levels and getting output like "1 year 1 second".
        break;
      }

      if ($granularity == 0) {
        break;
      }
    }
    return $output ? $output : $this->t('0 sec', array(), array('langcode' => $langcode));
  }

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
  public function getSampleDateFormats($langcode = NULL, $timestamp = NULL, $timezone = NULL) {
    $timestamp = $timestamp ?: time();
    // All date format characters for the PHP date() function.
    $date_chars = str_split('dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU');
    $date_elements = array_combine($date_chars, $date_chars);
    return array_map(function ($character) use ($timestamp, $timezone, $langcode) {
      return $this->format($timestamp, 'custom', $character, $timezone, $langcode);
    }, $date_elements);
  }

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
   *
   * @return string
   *   A translated string representation of the difference between the given
   *   timestamp and the current request time. This interval is always positive.
   *
   * @see \Drupal\Core\Datetime\DateFormatter::formatDiff()
   * @see \Drupal\Core\Datetime\DateFormatter::formatTimeDiffSince()
   */
  public function formatTimeDiffUntil($timestamp, $options = array()) {
    $request_time = $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
    return $this->formatDiff($request_time, $timestamp, $options);
  }

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
   *
   * @return string
   *   A translated string representation of the difference between the given
   *   timestamp and the current request time. This interval is always positive.
   *
   * @see \Drupal\Core\Datetime\DateFormatter::formatDiff()
   * @see \Drupal\Core\Datetime\DateFormatter::formatTimeDiffUntil()
   */
  public function formatTimeDiffSince($timestamp, $options = array()) {
    $request_time = $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
    return $this->formatDiff($timestamp, $request_time, $options);
  }

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
   *
   * @return string
   *   A translated string representation of the interval. This interval is
   *   always positive.
   *
   * @see \Drupal\Core\Datetime\DateFormatter::formatInterval()
   * @see \Drupal\Core\Datetime\DateFormatter::formatTimeDiffSince()
   * @see \Drupal\Core\Datetime\DateFormatter::formatTimeDiffUntil()
   */
  public function formatDiff($from, $to, $options = array()) {

    $options += array(
      'granularity' => 2,
      'langcode' => NULL,
      'strict' => TRUE,
    );

    if ($options['strict'] && $from > $to) {
      return $this->t('0 seconds');
    }

    $date_time_from = new \DateTime();
    $date_time_from->setTimestamp($from);

    $date_time_to = new \DateTime();
    $date_time_to->setTimestamp($to);

    $interval = $date_time_to->diff($date_time_from);

    $granularity = $options['granularity'];
    $output = '';

    // We loop over the keys provided by \DateInterval explicitly. Since we
    // don't take the "invert" property into account, the resulting output value
    // will always be positive.
    foreach (array('y', 'm', 'd', 'h', 'i', 's') as $value) {
      if ($interval->$value > 0) {
        // Switch over the keys to call formatPlural() explicitly with literal
        // strings for all different possibilities.
        switch ($value) {
          case 'y':
            $interval_output = $this->formatPlural($interval->y, '1 year', '@count years', array(), array('langcode' => $options['langcode']));
            break;

          case 'm':
            $interval_output = $this->formatPlural($interval->m, '1 month', '@count months', array(), array('langcode' => $options['langcode']));
            break;

          case 'd':
            // \DateInterval doesn't support weeks, so we need to calculate them
            // ourselves.
            $interval_output = '';
            $days = $interval->d;
            $weeks = floor($days / 7);
            if ($weeks) {
              $interval_output .= $this->formatPlural($weeks, '1 week', '@count weeks', array(), array('langcode' => $options['langcode']));
              $days -= $weeks * 7;
              $granularity--;
            }

            if ((!$output || $weeks > 0) && $granularity > 0 && $days > 0) {
              $interval_output .= ($interval_output ? ' ' : '') . $this->formatPlural($days, '1 day', '@count days', array(), array('langcode' => $options['langcode']));
            }
            else {
              // If we did not output days, set the granularity to 0 so that we
              // will not output hours and get things like "1 week 1 hour".
              $granularity = 0;
            }
            break;

          case 'h':
            $interval_output = $this->formatPlural($interval->h, '1 hour', '@count hours', array(), array('langcode' => $options['langcode']));
            break;

          case 'i':
            $interval_output = $this->formatPlural($interval->i, '1 minute', '@count minutes', array(), array('langcode' => $options['langcode']));
            break;

          case 's':
            $interval_output = $this->formatPlural($interval->s, '1 second', '@count seconds', array(), array('langcode' => $options['langcode']));
            break;

        }
        $output .= ($output && $interval_output ? ' ' : '') . $interval_output;
        $granularity--;
      }
      elseif ($output) {
        // Break if there was previous output but not any output at this level,
        // to avoid skipping levels and getting output like "1 year 1 second".
        break;
      }

      if ($granularity <= 0) {
        break;
      }
    }

    if (empty($output)) {
      $output = $this->t('0 seconds');
    }

    return $output;
  }

  /**
   * Loads the given format pattern for the given langcode.
   *
   * @param string $format
   *   The machine name of the date format.
   * @param string $langcode
   *   The langcode of the language to use.
   *
   * @return string|null
   *   The pattern for the date format in the given language for non-custom
   *   formats, NULL otherwise.
   */
  protected function dateFormat($format, $langcode) {
    if (!isset($this->dateFormats[$format][$langcode])) {
      $original_language = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(array('id' => $langcode)));
      $this->dateFormats[$format][$langcode] = $this->dateFormatStorage->load($format);
      $this->languageManager->setConfigOverrideLanguage($original_language);
    }
    return $this->dateFormats[$format][$langcode];
  }

  /**
   * Returns the default country from config.
   *
   * @return string
   *   The config setting for country.default.
   */
  protected function country() {
    if ($this->country === NULL) {
      $this->country = \Drupal::config('system.date')->get('country.default');
    }
    return $this->country;
  }

}

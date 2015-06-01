<?php

/**
 * @file
 * Contains \Drupal\Core\Datetime\Date.
 */

namespace Drupal\Core\Datetime;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, TranslationInterface $translation, ConfigFactoryInterface $config_factory) {
    $this->dateFormatStorage = $entity_manager->getStorage('date_format');
    $this->languageManager = $language_manager;
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
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
   * @param string $timezone
   *   (optional) Time zone identifier, as described at
   *   http://php.net/manual/timezones.php Defaults to the time zone used to
   *   display the page.
   * @param string $langcode
   *   (optional) Language code to translate to. Defaults to the language used to
   *   display the page.
   *
   * @return string
   *   A translated date string in the requested format.
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
    return Xss::filter($date->format($format, $settings));
  }

  /**
   * Formats a time interval with the requested granularity.
   *
   * @param int $interval
   *   The length of the interval in seconds.
   * @param int $granularity
   *   (optional) How many different units to display in the string (2 by
   *   default).
   * @param string $langcode
   *   (optional) Language code to translate to a language other than what is
   *   used to display the page. Defaults to NULL.
   *
   * @return string
   *   A translated string representation of the interval.
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
    return array_map(function($character) use ($timestamp, $timezone, $langcode) {
      return $this->format($timestamp, 'custom', $character, $timezone, $langcode);
    }, $date_elements);
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

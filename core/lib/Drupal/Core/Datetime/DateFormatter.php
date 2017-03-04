<?php

namespace Drupal\Core\Datetime;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a service to handle various date related functionality.
 *
 * @ingroup i18n
 */
class DateFormatter implements DateFormatterInterface {
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
  protected $dateFormats = [];

  /**
   * Contains the different date interval units.
   *
   * This array is keyed by strings representing the unit (e.g.
   * '1 year|@count years') and with the amount of values of the unit in
   * seconds.
   *
   * @var array
   */
  protected $units = [
    '1 year|@count years' => 31536000,
    '1 month|@count months' => 2592000,
    '1 week|@count weeks' => 604800,
    '1 day|@count days' => 86400,
    '1 hour|@count hours' => 3600,
    '1 min|@count min' => 60,
    '1 sec|@count sec' => 1,
  ];

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
   * {@inheritdoc}
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
    $create_settings = [
      'langcode' => $langcode,
      'country' => $this->country(),
    ];
    $date = DrupalDateTime::createFromTimestamp($timestamp, $this->timezones[$timezone], $create_settings);

    // If we have a non-custom date format use the provided date format pattern.
    if ($type !== 'custom') {
      if ($date_format = $this->dateFormat($type, $langcode)) {
        $format = $date_format->getPattern();
      }
    }

    // Fall back to the 'medium' date format type if the format string is
    // empty, either from not finding a requested date format or being given an
    // empty custom format string.
    if (empty($format)) {
      $format = $this->dateFormat('fallback', $langcode)->getPattern();
    }

    // Call $date->format().
    $settings = [
      'langcode' => $langcode,
    ];
    return $date->format($format, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function formatInterval($interval, $granularity = 2, $langcode = NULL) {
    $output = '';
    foreach ($this->units as $key => $value) {
      $key = explode('|', $key);
      if ($interval >= $value) {
        $output .= ($output ? ' ' : '') . $this->formatPlural(floor($interval / $value), $key[0], $key[1], [], ['langcode' => $langcode]);
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
    return $output ? $output : $this->t('0 sec', [], ['langcode' => $langcode]);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function formatTimeDiffUntil($timestamp, $options = []) {
    $request_time = $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
    return $this->formatDiff($request_time, $timestamp, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatTimeDiffSince($timestamp, $options = []) {
    $request_time = $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
    return $this->formatDiff($timestamp, $request_time, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatDiff($from, $to, $options = []) {

    $options += [
      'granularity' => 2,
      'langcode' => NULL,
      'strict' => TRUE,
      'return_as_object' => FALSE,
    ];

    if ($options['strict'] && $from > $to) {
      $string = $this->t('0 seconds');
      if ($options['return_as_object']) {
        return new FormattedDateDiff($string, 0);
      }
      return $string;
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
    $max_age = 1e99;
    foreach (['y', 'm', 'd', 'h', 'i', 's'] as $value) {
      if ($interval->$value > 0) {
        // Switch over the keys to call formatPlural() explicitly with literal
        // strings for all different possibilities.
        switch ($value) {
          case 'y':
            $interval_output = $this->formatPlural($interval->y, '1 year', '@count years', [], ['langcode' => $options['langcode']]);
            $max_age = min($max_age, 365 * 86400);
            break;

          case 'm':
            $interval_output = $this->formatPlural($interval->m, '1 month', '@count months', [], ['langcode' => $options['langcode']]);
            $max_age = min($max_age, 30 * 86400);
            break;

          case 'd':
            // \DateInterval doesn't support weeks, so we need to calculate them
            // ourselves.
            $interval_output = '';
            $days = $interval->d;
            $weeks = floor($days / 7);
            if ($weeks) {
              $interval_output .= $this->formatPlural($weeks, '1 week', '@count weeks', [], ['langcode' => $options['langcode']]);
              $days -= $weeks * 7;
              $granularity--;
              $max_age = min($max_age, 7 * 86400);
            }

            if ((!$output || $weeks > 0) && $granularity > 0 && $days > 0) {
              $interval_output .= ($interval_output ? ' ' : '') . $this->formatPlural($days, '1 day', '@count days', [], ['langcode' => $options['langcode']]);
              $max_age = min($max_age, 86400);
            }
            else {
              // If we did not output days, set the granularity to 0 so that we
              // will not output hours and get things like "1 week 1 hour".
              $granularity = 0;
            }
            break;

          case 'h':
            $interval_output = $this->formatPlural($interval->h, '1 hour', '@count hours', [], ['langcode' => $options['langcode']]);
            $max_age = min($max_age, 3600);
            break;

          case 'i':
            $interval_output = $this->formatPlural($interval->i, '1 minute', '@count minutes', [], ['langcode' => $options['langcode']]);
            $max_age = min($max_age, 60);
            break;

          case 's':
            $interval_output = $this->formatPlural($interval->s, '1 second', '@count seconds', [], ['langcode' => $options['langcode']]);
            $max_age = min($max_age, 1);
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
      $max_age = 0;
    }

    if ($options['return_as_object']) {
      return new FormattedDateDiff($output, $max_age);
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
   * @return \Drupal\Core\Datetime\DateFormatInterface|null
   *   The configuration entity for the date format in the given language for
   *   non-custom formats, NULL otherwise.
   */
  protected function dateFormat($format, $langcode) {
    if (!isset($this->dateFormats[$format][$langcode])) {
      $original_language = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(['id' => $langcode]));
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

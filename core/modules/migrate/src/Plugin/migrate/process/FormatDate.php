<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts date/datetime from one format to another.
 *
 * Available configuration keys
 * - from_format: The source format string as accepted by
 *   @link http://php.net/manual/datetime.createfromformat.php \DateTime::createFromFormat. @endlink
 * - to_format: The destination format.
 * - timezone: (deprecated) String identifying the required time zone, see
 *   DateTimePlus::__construct(). The timezone configuration key is deprecated
 *   in Drupal 8.4.x and will be removed before Drupal 9.0.0, use from_timezone
 *   and to_timezone instead.
 * - from_timezone: String identifying the required source time zone, see
 *   DateTimePlus::__construct().
 * - to_timezone: String identifying the required destination time zone, see
 *   DateTimePlus::__construct().
 * - settings: keyed array of settings, see DateTimePlus::__construct().
 *
 * Configuration keys from_timezone and to_timezone are both optional. Possible
 * input variants:
 * - Both from_timezone and to_timezone are empty. Date will not be converted
 *   and be treated as date in default timezone.
 * - Only from_timezone is set. Date will be converted from timezone specified
 *   in from_timezone key to the default timezone.
 * - Only to_timezone is set. Date will be converted from the default timezone
 *   to the timezone specified in to_timezone key.
 * - Both from_timezone and to_timezone are set. Date will be converted from
 *   timezone specified in from_timezone key to the timezone specified in
 *   to_timezone key.
 *
 * Examples:
 *
 * Example usage for date only fields
 * (DateTimeItemInterface::DATE_STORAGE_FORMAT):
 * @code
 * process:
 *   field_date:
 *     plugin: format_date
 *     from_format: 'm/d/Y'
 *     to_format: 'Y-m-d'
 *     source: event_date
 * @endcode
 *
 * If the source value was '01/05/1955' the transformed value would be
 * 1955-01-05.
 *
 * Example usage for datetime fields
 * (DateTimeItemInterface::DATETIME_STORAGE_FORMAT):
 * @code
 * process:
 *   field_time:
 *     plugin: format_date
 *     from_format: 'm/d/Y H:i:s'
 *     to_format: 'Y-m-d\TH:i:s'
 *     source: event_time
 * @endcode
 *
 * If the source value was '01/05/1955 10:43:22' the transformed value would be
 * 1955-01-05T10:43:22.
 *
 * Example usage for datetime fields with a timezone and settings:
 * @code
 * process:
 *   field_time:
 *     plugin: format_date
 *     from_format: 'Y-m-d\TH:i:sO'
 *     to_format: 'Y-m-d\TH:i:s'
 *     from_timezone: 'America/Managua'
 *     to_timezone: 'UTC'
 *     settings:
 *       validate_format: false
 *     source: event_time
 * @endcode
 *
 * If the source value was '2004-12-19T10:19:42-0600' the transformed value
 * would be 2004-12-19T10:19:42. Set validate_format to false if your source
 * value is '0000-00-00 00:00:00'.
 *
 * @see \DateTime::createFromFormat()
 * @see \Drupal\Component\Datetime\DateTimePlus::__construct()
 * @see \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "format_date"
 * )
 */
class FormatDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) && $value !== '0' && $value !== 0) {
      return '';
    }

    // Validate the configuration.
    if (empty($this->configuration['from_format'])) {
      throw new MigrateException('Format date plugin is missing from_format configuration.');
    }
    if (empty($this->configuration['to_format'])) {
      throw new MigrateException('Format date plugin is missing to_format configuration.');
    }

    $fromFormat = $this->configuration['from_format'];
    $toFormat = $this->configuration['to_format'];
    if (isset($this->configuration['timezone'])) {
      @trigger_error('Configuration key "timezone" is deprecated in 8.4.x and will be removed before Drupal 9.0.0, use "from_timezone" and "to_timezone" instead. See https://www.drupal.org/node/2885746', E_USER_DEPRECATED);
      $from_timezone = $this->configuration['timezone'];
      $to_timezone = isset($this->configuration['to_timezone']) ? $this->configuration['to_timezone'] : NULL;
    }
    else {
      $system_timezone = date_default_timezone_get();
      $default_timezone = !empty($system_timezone) ? $system_timezone : 'UTC';
      $from_timezone = isset($this->configuration['from_timezone']) ? $this->configuration['from_timezone'] : $default_timezone;
      $to_timezone = isset($this->configuration['to_timezone']) ? $this->configuration['to_timezone'] : $default_timezone;
    }
    $settings = isset($this->configuration['settings']) ? $this->configuration['settings'] : [];

    // Attempts to transform the supplied date using the defined input format.
    // DateTimePlus::createFromFormat can throw exceptions, so we need to
    // explicitly check for problems.
    try {
      $transformed = DateTimePlus::createFromFormat($fromFormat, $value, $from_timezone, $settings)->format($toFormat, ['timezone' => $to_timezone]);
    }
    catch (\InvalidArgumentException $e) {
      throw new MigrateException(sprintf('Format date plugin could not transform "%s" using the format "%s". Error: %s', $value, $fromFormat, $e->getMessage()), $e->getCode(), $e);
    }
    catch (\UnexpectedValueException $e) {
      throw new MigrateException(sprintf('Format date plugin could not transform "%s" using the format "%s". Error: %s', $value, $fromFormat, $e->getMessage()), $e->getCode(), $e);
    }

    return $transformed;
  }

}

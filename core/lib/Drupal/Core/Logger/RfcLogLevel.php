<?php

namespace Drupal\Core\Logger;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @defgroup logging_severity_levels Logging severity levels
 * @{
 * Logging severity levels as defined in RFC 5424.
 *
 * The constant definitions of this class correspond to the logging severity
 * levels defined in RFC 5424, section 6.2.1. PHP supplies predefined LOG_*
 * constants for use in the syslog() function, but their values on Windows
 * builds do not correspond to RFC 5424. The associated PHP bug report was
 * closed with the comment, "And it's also not a bug, as Windows just have less
 * log levels," and "So the behavior you're seeing is perfectly normal."
 *
 * @see https://tools.ietf.org/html/rfc5424#section-6.2.1
 * @see http://bugs.php.net/bug.php?id=18090
 * @see http://php.net/manual/function.syslog.php
 * @see http://php.net/manual/network.constants.php
 * @see \Drupal\Core\Logger\RfcLogLevel::getLevels()
 *
 * @}
 */

/**
 * Defines various logging severity levels.
 *
 * @ingroup logging_severity_levels
 */
class RfcLogLevel {

  /**
   * Log message severity -- Emergency: system is unusable.
   */
  const EMERGENCY = 0;

  /**
   * Log message severity -- Alert: action must be taken immediately.
   */
  const ALERT = 1;

  /**
   * Log message severity -- Critical conditions.
   */
  const CRITICAL = 2;

  /**
   * Log message severity -- Error conditions.
   */
  const ERROR = 3;

  /**
   * Log message severity -- Warning conditions.
   */
  const WARNING = 4;

  /**
   * Log message severity -- Normal but significant conditions.
   */
  const NOTICE = 5;

  /**
   * Log message severity -- Informational messages.
   */
  const INFO = 6;

  /**
   * Log message severity -- Debug-level messages.
   */
  const DEBUG = 7;

  /**
   * An array with the severity levels as keys and labels as values.
   *
   * @var array
   */
  protected static $levels;

  /**
   * Returns a list of severity levels, as defined in RFC 5424.
   *
   * @return array
   *   Array of the possible severity levels for log messages.
   *
   * @see http://tools.ietf.org/html/rfc5424
   * @ingroup logging_severity_levels
   */
  public static function getLevels() {
    if (!static::$levels) {
      static::$levels = [
        static::EMERGENCY => new TranslatableMarkup('Emergency'),
        static::ALERT => new TranslatableMarkup('Alert'),
        static::CRITICAL => new TranslatableMarkup('Critical'),
        static::ERROR => new TranslatableMarkup('Error'),
        static::WARNING => new TranslatableMarkup('Warning'),
        static::NOTICE => new TranslatableMarkup('Notice'),
        static::INFO => new TranslatableMarkup('Info'),
        static::DEBUG => new TranslatableMarkup('Debug'),
      ];
    }

    return static::$levels;
  }

}

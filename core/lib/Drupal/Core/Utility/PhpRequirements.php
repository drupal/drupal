<?php

namespace Drupal\Core\Utility;

/**
 * Provides an object for dynamically identifying the minimum supported PHP.
 */
final class PhpRequirements {

  /**
   * The minimum PHP version requirement for the installed Drupal version.
   *
   * This property is maintained to make the class testable.
   *
   * @var string
   *
   * @see version_compare()
   */
  private static $drupalMinimumPhp = \Drupal::MINIMUM_PHP;

  /**
   * The expected PHP version end-of-life dates, keyed by PHP minor version.
   *
   * The array keys are in 'major.minor' format, and the date values are in ISO
   * 8601 format.
   *
   * @var string[]
   *   An array of end-of-life dates in ISO 8601 format, keyed by the PHP minor
   *   version in 'major.minor' format. The list must be sorted in an ascending
   *   order by the date. Multiple versions EOL on the same day must be sorted
   *   by the PHP version.
   */
  private static $phpEolDates = [
    '8.1' => '2024-11-25',
    '8.2' => '2025-12-08',
    '8.3' => '2026-11-23',
  ];

  /**
   * This class should not be instantiated.
   */
  private function __construct() {
  }

  /**
   * Dynamically identifies the minimum supported PHP version based on the date.
   *
   * Drupal automatically increases the minimum supported PHP version from
   * \Drupal::MINIMUM_PHP to a newer version after PHP's documented end-of-life
   * date for the previous version.
   *
   * Below this version:
   * - New sites can be installed (to allow update deployment workflows that
   *   reinstall sites from configuration), but a warning is displayed in the
   *   installer that the PHP version is too old (except within tests).
   * - Updates from previous Drupal versions can be run, but users are warned
   *   that Drupal no longer supports that PHP version.
   * - An error is shown in the status report that the PHP version is too old.
   *
   * @param \DateTime|null $date
   *   The DateTime to check. Defaults to the current datetime (now) if NULL.
   *
   * @return string
   *   The minimum supported PHP version on the date in a PHP-standardized
   *   number format supported by version_compare(). For example, '8.0.2' or
   *   '8.1'. This will be the lowest PHP version above the minimum PHP version
   *   supported by Drupal that is still supported, or the highest known PHP
   *   version if no known versions are still supported.
   *
   * @see version_compare()
   */
  public static function getMinimumSupportedPhp(?\DateTime $date = NULL): string {
    // By default, use the current date (right now).
    $date = $date ?? new \DateTime('now');

    // In case no data are available or all known PHP versions in this class
    // are already end-of-life, default to the version that had the most recent
    // end-of-life (the key of the last element in the sorted array).
    // The string cast ensures the value is a string, even if the PHP EOL date
    // array is empty. As of PHP 8.1, version_compare() no longer accepts NULL
    // as a parameter; empty string must be used instead.
    $lowest_supported_version = (string) array_key_last(static::$phpEolDates);

    // Next, look at versions that are end-of-life after the current date.
    // Find the lowest PHP version that is still supported.
    foreach (static::$phpEolDates as $version => $eol_date) {
      $eol_datetime = new \DateTime($eol_date);

      if ($eol_datetime > $date) {
        // If $version is less than the previously discovered lowest supported
        // version, use $version as the lowest supported version instead.
        if (version_compare($version, $lowest_supported_version) < 0) {
          $lowest_supported_version = $version;
        }
      }
    }

    // If PHP versions older than the Drupal minimum PHP version are still
    // supported, return Drupal minimum PHP version instead.
    if (version_compare($lowest_supported_version, static::$drupalMinimumPhp) < 0) {
      return static::$drupalMinimumPhp;
    }

    // Otherwise, return the lowest supported PHP version.
    return $lowest_supported_version;
  }

}

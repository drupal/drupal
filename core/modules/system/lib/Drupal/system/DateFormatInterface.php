<?php

/**
 * @file
 * Contains \Drupal\system\DateFormatInterface.
 */

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides an interface defining a date format.
 */
interface DateFormatInterface extends ConfigEntityInterface {

  /**
   * Gets the date pattern string for this format.
   *
   * @param string $type
   *   The date pattern type to set.
   *
   * @return string
   *   The pattern string as expected by date().
   */
  public function getPattern($type = DrupalDateTime::PHP);

  /**
   * Sets the date pattern for this format.
   *
   * @param string $pattern
   *   The date pattern to use for this format.
   * @param string $type
   *   The date pattern type to set.
   *
   * @return self
   *   Returns the date format.
   */
  public function setPattern($pattern, $type = DrupalDateTime::PHP);

  /**
   * Adds a locale for this date format.
   *
   * @param string $locale
   *   The locale to add for this format.
   *
   * @return self
   *   Returns the date format.
   */
  public function addLocale($locale);

  /**
   * Sets the locales for this date format. This overwrites existing locales.
   *
   * @param array $locales
   *   The array of locales to set for this format.
   *
   * @return self
   *   Returns the date format.
   */
  public function setLocales(array $locales);

  /**
   * Returns an array of the locales for this date format.
   *
   * @return array
   *   An array of locale names.
   */
  public function getLocales();

  /**
   * Determines if this data format has any locales.
   *
   * @return bool
   *   TRUE if the date format has locales, FALSE otherwise.
   */
  public function hasLocales();

  /**
   * Determines if this date format is locked.
   *
   * @return bool
   *   TRUE if the date format is locked, FALSE otherwise.
   */
  public function isLocked();

}

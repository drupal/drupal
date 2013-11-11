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
   * Determines if this date format is locked.
   *
   * @return bool
   *   TRUE if the date format is locked, FALSE otherwise.
   */
  public function isLocked();

}

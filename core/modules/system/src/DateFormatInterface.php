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
   * @return string
   *   The pattern string as expected by date().
   */
  public function getPattern();

  /**
   * Sets the date pattern for this format.
   *
   * @param string $pattern
   *   The date pattern to use for this format.
   *
   * @return $this
   */
  public function setPattern($pattern);

  /**
   * Determines if this date format is locked.
   *
   * @return bool
   *   TRUE if the date format is locked, FALSE otherwise.
   */
  public function isLocked();

}

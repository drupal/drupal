<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\DateTimeInterface.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Interface for dates, optionally including a time.
 *
 * @ingroup typed_data
 */
interface DateTimeInterface {

  /**
   * Returns the date time object.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   A date object or NULL if there is no date.
   */
  public function getDateTime();

  /**
   * Sets the date time object.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $dateTime
   *   An instance of a date time object.
   */
  public function setDateTime(DrupalDateTime $dateTime);

}

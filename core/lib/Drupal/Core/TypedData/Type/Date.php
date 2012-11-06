<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\Type\Date.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\TypedDataInterface;
use InvalidArgumentException;

/**
 * The date data type.
 *
 * The plain value of a date is an instance of the DrupalDateTime class. For setting
 * the value any value supported by the __construct() of the DrupalDateTime
 * class will work, including a DateTime object, a timestamp, a string
 * date, or an array of date parts.
 */
class Date extends TypedData implements TypedDataInterface {

  /**
   * The data value.
   *
   * @var DateTime
   */
  protected $value;

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($value) {

    // Don't try to create a date from an empty value.
    // It would default to the current time.
    if (!isset($value)) {
      $this->value = $value;
    }
    else {
      $this->value = $value instanceOf DrupalDateTime ? $value : new DrupalDateTime($value);
      if ($this->value->hasErrors()) {
        throw new InvalidArgumentException("Invalid date format given.");
      }
    }
  }

  /**
   * Implements TypedDataInterface::getString().
   */
  public function getString() {
    return (string) $this->getValue();
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // TODO: Implement validate() method.
  }
}

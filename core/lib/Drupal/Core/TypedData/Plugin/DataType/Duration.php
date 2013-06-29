<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Duration.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\TypedData\TypedData;
use DateInterval;

/**
 * The duration data type.
 *
 * The plain value of a duration is an instance of the DateInterval class. For
 * setting the value an instance of the DateInterval class, a ISO8601 string as
 * supported by DateInterval::__construct, or an integer in seconds may be
 * passed.
 *
 * @DataType(
 *   id = "duration",
 *   label = @Translation("Duration"),
 *   primitive_type = 6
 * )
 */
class Duration extends TypedData {

  /**
   * The data value.
   *
   * @var \DateInterval
   */
  protected $value;

  /**
   * Overrides TypedData::setValue().
   */
  public function setValue($value, $notify = TRUE) {
    // Notify the parent of any changes to be made.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
    // Catch any exceptions thrown due to invalid values being passed.
    try {
      if ($value instanceof DateInterval || !isset($value)) {
        $this->value = $value;
      }
      // Treat integer values as time spans in seconds, even if supplied as PHP
      // string.
      elseif ((string) (int) $value === (string) $value) {
        $this->value = new DateInterval('PT' . $value . 'S');
      }
      elseif (is_string($value)) {
        // @todo: Add support for negative intervals on top of the DateInterval
        // constructor.
        $this->value = new DateInterval($value);
      }
      else {
        // Unknown value given.
        $this->value = $value;
      }
    }
    catch (\Exception $e) {
      // An invalid value has been given. Setting any invalid value will let
      // validation fail.
      $this->value = $e;
    }
  }

  /**
   * Overrides TypedData::getString().
   */
  public function getString() {
    // Generate an ISO 8601 formatted string as supported by
    // DateInterval::__construct() and setValue().
    return (string) $this->getValue()->format('%rP%yY%mM%dDT%hH%mM%sS');
  }
}

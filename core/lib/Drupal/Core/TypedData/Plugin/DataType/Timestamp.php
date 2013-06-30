<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Timestamp.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\Type\DateTimeInterface;

/**
 * The timestamp data type.
 *
 * @DataType(
 *   id = "timestamp",
 *   label = @Translation("String")
 * )
 */
class Timestamp extends Integer implements DateTimeInterface {

  /**
   * The data value as a UNIX timestamp.
   *
   * @var integer
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getDateTime() {
    if ($this->value) {
      return new DrupalDateTime($this->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime) {
    $this->value = $dateTime->getTimestamp();
  }
}

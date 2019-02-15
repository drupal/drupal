<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\Type\DateTimeInterface;

/**
 * The timestamp data type.
 *
 * @DataType(
 *   id = "timestamp",
 *   label = @Translation("Timestamp")
 * )
 */
class Timestamp extends IntegerData implements DateTimeInterface {

  /**
   * The data value as a UNIX timestamp.
   *
   * @var int
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getDateTime() {
    if (isset($this->value)) {
      return DrupalDateTime::createFromTimestamp($this->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime, $notify = TRUE) {
    $this->value = $dateTime->getTimestamp();
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}

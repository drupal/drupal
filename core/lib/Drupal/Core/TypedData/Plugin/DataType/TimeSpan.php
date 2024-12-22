<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\Serialization\Attribute\JsonSchema;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Type\DurationInterface;

/**
 * The time span data type represents durations as number of seconds.
 *
 * The plain value is the (integer) number of seconds. Note that time spans only
 * map correctly to durations as long as the number of seconds does not exceed
 * a day (there is already a difference in applying a duration of a day or 24
 * hours due to daylight savings). If that's an issue, consider using
 * \Drupal\Core\TypedData\Type\DurationIso8601 instead.
 *
 * @see \Drupal\Core\TypedData\Type\DurationIso8601
 */
#[DataType(
  id: "timespan",
  label: new TranslatableMarkup("Time span in seconds"),
)]
class TimeSpan extends IntegerData implements DurationInterface {

  /**
   * {@inheritdoc}
   */
  public function getDuration() {
    if ($this->value) {
      // Keep the duration in seconds as there is generally no valid way to
      // convert it to days, months or years.
      return new \DateInterval($this->getDurationAsIso8601Abnf());
    }
  }

  /**
   * {@inheritdoc}
   */
  #[JsonSchema(['type' => 'string', 'format' => 'duration'])]
  public function getDurationAsIso8601Abnf(): string {
    return 'PT' . $this->value . 'S';
  }

  /**
   * {@inheritdoc}
   */
  public function setDuration(\DateInterval $duration, $notify = TRUE) {
    // Note that this applies the assumption of 12 month's a 30 days and
    // each year having 365 days. There is no accurate conversion for time spans
    // exceeding a day.
    $this->value = ($duration->y * 365 * 24 * 60 * 60) +
      ($duration->m * 30 * 24 * 60 * 60) +
      ($duration->d * 24 * 60 * 60) +
      ($duration->h * 60 * 60) +
      ($duration->i * 60) +
       $duration->s;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}

<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Type\DateTimeInterface;

/**
 * A data type for ISO 8601 date strings.
 *
 * The plain value of this data type is a date string in ISO 8601 format.
 */
#[DataType(
  id: "datetime_iso8601",
  label: new TranslatableMarkup("Date")
)]
class DateTimeIso8601 extends StringData implements DateTimeInterface {

  /**
   * {@inheritdoc}
   */
  public function getDateTime() {
    if ($this->value) {
      if (is_array($this->value)) {
        // Data of this type must always be stored in UTC.
        $datetime = DrupalDateTime::createFromArray($this->value, 'UTC');
      }
      else {
        // Data of this type must always be stored in UTC.
        $datetime = new DrupalDateTime($this->value, 'UTC');
      }
      return $datetime;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime, $notify = TRUE) {
    $this->value = $dateTime->format('c');
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}

<?php

namespace Drupal\datetime;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A computed property for dates of date time field items.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - date source: The date property containing the to be computed date.
 */
class DateTimeComputed extends TypedData {

  /**
   * Cached computed date.
   *
   * @var \DateTime|null
   */
  protected $date = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('date source')) {
      throw new \InvalidArgumentException("The definition's 'date source' key has to specify the name of the date property to be computed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->date !== NULL) {
      return $this->date;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $value = $item->{($this->definition->getSetting('date source'))};

    $datetime_type = $item->getFieldDefinition()->getSetting('datetime_type');
    $storage_format = $datetime_type === DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    try {
      $date = DrupalDateTime::createFromFormat($storage_format, $value, DateTimeItemInterface::STORAGE_TIMEZONE);
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $this->date = $date;
        // If the format did not include an explicit time portion, then the
        // time will be set from the current time instead. For consistency, we
        // set the time to 12:00:00 UTC for date-only fields. This is used so
        // that the local date portion is the same, across nearly all time
        // zones.
        // @see \Drupal\Component\Datetime\DateTimePlus::setDefaultDateTime()
        // @see http://php.net/manual/datetime.createfromformat.php
        if ($datetime_type === DateTimeItem::DATETIME_TYPE_DATE) {
          $this->date->setDefaultDateTime();
        }
      }
    }
    catch (\Exception $e) {
      // @todo Handle this.
    }
    return $this->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->date = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}

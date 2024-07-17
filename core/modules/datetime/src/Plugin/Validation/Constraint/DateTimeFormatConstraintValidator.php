<?php

namespace Drupal\datetime\Plugin\Validation\Constraint;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for DateTime items to ensure the format is correct.
 */
class DateTimeFormatConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint): void {
    /** @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeItem $item */
    if (isset($item)) {
      $value = $item->getValue()['value'];
      if (!is_string($value)) {
        $this->context->addViolation($constraint->badType);
      }
      else {
        $datetime_type = $item->getFieldDefinition()->getSetting('datetime_type');
        $format = $datetime_type === DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
        $date = NULL;
        try {
          $date = DateTimePlus::createFromFormat($format, $value, new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
        }
        catch (\InvalidArgumentException) {
          $this->context->addViolation($constraint->badFormat, [
            '@value' => $value,
            '@format' => $format,
          ]);
          return;
        }
        catch (\UnexpectedValueException) {
          $this->context->addViolation($constraint->badValue, [
            '@value' => $value,
            '@format' => $format,
          ]);
          return;
        }
        if ($date === NULL || $date->hasErrors()) {
          $this->context->addViolation($constraint->badFormat, [
            '@value' => $value,
            '@format' => $format,
          ]);
        }
      }
    }
  }

}

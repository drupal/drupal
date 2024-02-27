<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for the Drupal 'range' constraint.
 */
class RangeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (!$constraint instanceof Range) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Range');
    }

    if (NULL === $value) {
      return;
    }

    if (!is_numeric($value) && !$value instanceof \DateTimeInterface) {
      $this->context->buildViolation($constraint->invalidMessage)
        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
        ->setCode(Range::INVALID_CHARACTERS_ERROR)
        ->addViolation();

      return;
    }

    $min = $constraint->min;
    $max = $constraint->max;

    // Convert strings to DateTimes if comparing another DateTime.
    // This allows to compare with any date/time value supported by
    // the DateTime constructor.
    // @see http://php.net/manual/en/datetime.formats.php
    if ($value instanceof \DateTimeInterface) {
      if (\is_string($min)) {
        $min = new \DateTime($min);
      }

      if (\is_string($max)) {
        $max = new \DateTime($max);
      }
    }

    $hasLowerLimit = NULL !== $constraint->min;
    $hasUpperLimit = NULL !== $constraint->max;

    if ($hasLowerLimit && $hasUpperLimit && ($value < $min || $value > $max)) {
      $this->context->buildViolation($constraint->notInRangeMessage)
        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
        ->setParameter('{{ min }}', $this->formatValue($min, self::PRETTY_DATE))
        ->setParameter('{{ max }}', $this->formatValue($max, self::PRETTY_DATE))
        ->setCode(Range::NOT_IN_RANGE_ERROR)
        ->addViolation();

      return;
    }

    if ($hasUpperLimit && $value > $max) {
      $this->context->buildViolation($constraint->maxMessage)
        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
        ->setParameter('{{ limit }}', $this->formatValue($max, self::PRETTY_DATE))
        ->setCode(Range::TOO_HIGH_ERROR)
        ->addViolation();

      return;
    }

    if ($hasLowerLimit && $value < $min) {
      $this->context->buildViolation($constraint->minMessage)
        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
        ->setParameter('{{ limit }}', $this->formatValue($min, self::PRETTY_DATE))
        ->setCode(Range::TOO_LOW_ERROR)
        ->addViolation();
    }
  }

}

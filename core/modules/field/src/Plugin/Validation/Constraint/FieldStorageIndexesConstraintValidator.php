<?php

declare(strict_types=1);

namespace Drupal\field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates FieldStorageIndexesConstraint.
 */
class FieldStorageIndexesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof FieldStorageIndexesConstraint) {
      throw new UnexpectedTypeException($constraint, FieldStorageIndexesConstraint::class);
    }

    if ($value === NULL || $value === []) {
      return;
    }

    if (!is_array($value)) {
      $this->context->buildViolation($constraint->message)->addViolation();
      return;
    }

    foreach ($value as $index_name => $columns) {
      if (!is_string($index_name) || $index_name === '') {
        $this->context->buildViolation($constraint->invalidIndexNameMessage)
          ->setParameter('@index', (string) $index_name)
          ->addViolation();
        continue;
      }

      if (!is_array($columns) || !array_is_list($columns)) {
        $this->context->buildViolation($constraint->invalidIndexMessage)
          ->setParameter('@index', $index_name)
          ->addViolation();
        continue;
      }

      foreach ($columns as $column) {
        if (is_string($column) && $column !== '') {
          continue;
        }

        if (is_array($column)) {
          if ($column === [] || !array_is_list($column) || count($column) != 2) {
            $this->context->buildViolation($constraint->invalidColumnMessage)
              ->setParameter('@index', $index_name)
              ->addViolation();
            continue;
          }

          $name = $column[0] ?? NULL;
          if (!is_string($name) || $name === '') {
            $this->context->buildViolation($constraint->invalidColumnMessage)
              ->setParameter('@index', $index_name)
              ->addViolation();
            continue;
          }

          if (count($column) === 2) {
            $length = $column[1];
            if (!is_int($length) || $length <= 0) {
              $this->context->buildViolation($constraint->invalidColumnLengthMessage)
                ->setParameter('@index', $index_name)
                ->setParameter('@column', $name)
                ->addViolation();
            }
          }

          continue;
        }

        $this->context->buildViolation($constraint->invalidColumnMessage)
          ->setParameter('@index', $index_name)
          ->addViolation();
      }
    }
  }

}

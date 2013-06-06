<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\ComplexDataConstraintValidator.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\ComplexDataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates complex data.
 */
class ComplexDataConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!isset($value)) {
      return;
    }

    if (!$value instanceof ComplexDataInterface) {
      throw new UnexpectedTypeException($value, 'ComplexData');
    }

    $group = $this->context->getGroup();

    foreach ($constraint->properties as $name => $constraints) {
      $property = $value->get($name);
      $is_container = $property instanceof ComplexDataInterface || $property instanceof ListInterface;
      if (!$is_container) {
        $property = $property->getValue();
      }
      elseif ($property->isEmpty()) {
        // @see \Drupal\Core\TypedData\Validation\PropertyContainerMetadata::accept();
        $property = NULL;
      }
      $this->context->validateValue($property, $constraints, $name, $group);
    }
  }
}

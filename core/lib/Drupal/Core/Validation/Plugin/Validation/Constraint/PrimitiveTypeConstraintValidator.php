<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\Type\BinaryInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Type\DurationInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\UriInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PrimitiveType constraint.
 */
class PrimitiveTypeConstraintValidator extends ConstraintValidator {

  /**
   * Implements \Symfony\Component\Validator\ConstraintValidatorInterface::validate().
   */
  public function validate($value, Constraint $constraint) {

    if (!isset($value)) {
      return;
    }

    $typed_data = $this->context->getMetadata()->getTypedData();
    $valid = TRUE;
    if ($typed_data instanceof BinaryInterface && !is_resource($value)) {
      $valid = FALSE;
    }
    if ($typed_data instanceof BooleanInterface && !(is_bool($value) || $value === 0 || $value === '0' || $value === 1 || $value == '1')) {
      $valid = FALSE;
    }
    if ($typed_data instanceof FloatInterface && filter_var($value, FILTER_VALIDATE_FLOAT) === FALSE) {
      $valid = FALSE;
    }
    if ($typed_data instanceof IntegerInterface && filter_var($value, FILTER_VALIDATE_INT) === FALSE) {
      $valid = FALSE;
    }
    if ($typed_data instanceof StringInterface && !is_scalar($value)) {
      $valid = FALSE;
    }
    if ($typed_data instanceof UriInterface && filter_var($value, FILTER_VALIDATE_URL) === FALSE) {
      $valid = FALSE;
    }
    // @todo: Move those to separate constraint validators.
    try {
      if ($typed_data instanceof DateTimeInterface && $typed_data->getDateTime() && $typed_data->getDateTime()->hasErrors()) {
        $valid = FALSE;
      }
      if ($typed_data instanceof DurationInterface && $typed_data->getDuration() && !($typed_data->getDuration() instanceof \DateInterval)) {
        $valid = FALSE;
      }
    }
    catch (\Exception $e) {
      // Invalid durations or dates might throw exceptions.
      $valid = FALSE;
    }

    if (!$valid) {
      // @todo: Provide a good violation message for each problem.
      $this->context->addViolation($constraint->message, array(
        '%value' => is_object($value) ? get_class($value) : (is_array($value) ? 'Array' : (string) $value)
      ));
    }
  }
}

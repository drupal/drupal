<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use DateInterval;
use Drupal\Core\TypedData\Primitive;
use Drupal\Core\Datetime\DrupalDateTime;
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

    switch ($constraint->type) {
      case Primitive::BINARY:
        $valid = is_resource($value);
        break;
      case Primitive::BOOLEAN:
        $valid = is_bool($value) || $value === 0 || $value === '0' || $value === 1 || $value == '1';
        break;
      case Primitive::DATE:
        $valid = $value instanceOf DrupalDateTime && !$value->hasErrors();
        break;
      case Primitive::DURATION:
        $valid = $value instanceof DateInterval;
        break;
      case Primitive::FLOAT:
        $valid = filter_var($value, FILTER_VALIDATE_FLOAT) !== FALSE;
        break;
      case Primitive::INTEGER:
        $valid = filter_var($value, FILTER_VALIDATE_INT) !== FALSE;
        break;
      case Primitive::STRING:
        // PHP integers, floats or booleans are valid strings also, so we
        // cannot use is_string() here.
        $valid = is_scalar($value);
        break;
      case Primitive::URI:
        $valid = filter_var($value, FILTER_VALIDATE_URL) ;
        break;
      default:
        $valid = FALSE;
        break;
    }

    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '%value' => is_object($value) ? get_class($value) : (is_array($value) ? 'Array' : (string) $value),
        '%type'  => $constraint->type,
      ));
    }
  }
}

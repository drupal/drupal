<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\Type\BinaryInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Type\DurationInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\UriInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Drupal\Component\Render\MarkupInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PrimitiveType constraint.
 */
class PrimitiveTypeConstraintValidator extends ConstraintValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function validate($value, Constraint $constraint) {

    if (!isset($value)) {
      return;
    }

    $typed_data = $this->getTypedData();
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
    if ($typed_data instanceof StringInterface && !is_scalar($value) && !($value instanceof MarkupInterface)) {
      $valid = FALSE;
    }
    // Ensure that URIs comply with http://tools.ietf.org/html/rfc3986, which
    // requires:
    // - That it is well formed (parse_url() returns FALSE if not).
    // - That it contains a scheme (parse_url(, PHP_URL_SCHEME) returns NULL if
    //   not).
    if ($typed_data instanceof UriInterface && in_array(parse_url($value, PHP_URL_SCHEME), [NULL, FALSE], TRUE)) {
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
      $this->context->addViolation($constraint->message, [
        '%value' => is_object($value) ? get_class($value) : (is_array($value) ? 'Array' : (string) $value),
      ]);
    }
  }

}

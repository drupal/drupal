<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the StringEqualsConcatenatedValues constraint.
 */
class StringEqualsConcatenatedValuesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }
    if (!$constraint instanceof StringEqualsConcatenatedValuesConstraint) {
      throw new UnexpectedTypeException($constraint, StringEqualsConcatenatedValuesConstraint::class);
    }

    assert($this->context->getObject() instanceof TypedDataInterface);
    $resolved_values = array_map(
      fn (string $expression): string => TypeResolver::resolveExpression($expression, $this->context->getObject()),
      $constraint->values
    );

    // Verify the required values are present; if not, that's a logical error in
    // the config schema, not in concrete config.
    $missing_properties = array_intersect($constraint->values, $resolved_values);
    if (!empty($missing_properties)) {
      $this->context->buildViolation('This validation constraint is configured to inspect the properties %properties, but some do not exist: %missing_properties.')
        ->setParameter('%properties', implode(', ', $constraint->values))
        ->setParameter('%missing_properties', implode(', ', $missing_properties))
        ->addViolation();
      return;
    }

    // Retrieve the values of the expected string.
    $expected_string_values = [];
    foreach ($constraint->values as $index => $reference) {
      $expected_string_values[] = $resolved_values[$index];
    }
    if ($constraint->reservedCharacters && $constraint->reservedCharactersSubstitute) {
      $expected_string_values = str_replace($constraint->reservedCharacters, $constraint->reservedCharactersSubstitute, $expected_string_values);
    }
    $expected_string = implode($constraint->separator, $expected_string_values);

    if ($expected_string !== $value) {
      $expected_format = implode(
        $constraint->separator,
        array_map(fn (string $v) => "<$v>", $constraint->values),
      );
      $this->context->addViolation($constraint->message, [
        '@value' => $value,
        '@expected_string' => $expected_string,
        '@expected_format' => $expected_format,
      ]);
    }
  }

}

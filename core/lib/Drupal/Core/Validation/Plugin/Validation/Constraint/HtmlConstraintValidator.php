<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Masterminds\HTML5;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator for Html constraint.
 */
class HtmlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof HtmlConstraint) {
      throw new UnexpectedTypeException($constraint, HtmlConstraint::class);
    }

    if ($value === NULL) {
      return;
    }
    if (!is_string($value) && !$value instanceof \Stringable) {
      throw new UnexpectedValueException($value, 'string|\Stringable');
    }

    $value = (string) $value;
    $parser = new HTML5(['disable_html_ns' => TRUE, 'encoding' => 'UTF-8']);

    match ($constraint->mode) {
      'fragment' => $parser->loadHTMLFragment($value),
      'document' => $parser->loadHTML($value),
    };

    foreach ($parser->getErrors() as $error) {
      $this->context->addViolation($error);
    }
  }

}

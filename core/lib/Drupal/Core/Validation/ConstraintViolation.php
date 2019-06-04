<?php

namespace Drupal\Core\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation as SymfonyConstraintViolation;

/**
 * ConstraintViolation subclass to handle markup in messages.
 *
 * In symfony 4, the message in a violation is typecast to string.  This class
 * allows for a markup object to be used instead.
 */
class ConstraintViolation extends SymfonyConstraintViolation {

  /**
   * The violation message, may be markup or a string.
   *
   * @var \Drupal\Component\Render\MarkupInterface|string
   */
  private $originalMessage;

  /**
   * Constructs a ConstraintViolation object.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $message
   *   The violation message.
   * @param string $messageTemplate
   *   The raw violation message.
   * @param array $parameters
   *   The parameters to substitute in the raw violation message.
   * @param mixed $root
   *   The value originally passed to the validator.
   * @param string|null $propertyPath
   *   The property path from the root value to the invalid value.
   * @param mixed $invalidValue
   *   The invalid value that caused this violation.
   * @param int|null $plural
   *   The number for determining the plural form when translating the message.
   * @param mixed $code
   *   The error code of the violation.
   * @param \Symfony\Component\Validator\Constraint|null $constraint
   *   The constraint whose validation caused the violation.
   * @param mixed $cause
   *   The cause of the violation.
   */
  public function __construct($message, $messageTemplate, array $parameters, $root, $propertyPath, $invalidValue, $plural = NULL, $code = NULL, Constraint $constraint = NULL, $cause = NULL) {
    $this->originalMessage = $message;
    parent::__construct($message, $messageTemplate, $parameters, $root, $propertyPath, $invalidValue, $plural, $code, $constraint, $cause);
  }

  /**
   * Returns the violation message.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The violation message
   */
  public function getMessage() {
    return $this->originalMessage;
  }

}

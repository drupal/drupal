<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates if a string conforms to the RFC 3986 host component.
 */
class UriHostConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($constraint instanceof UriHostConstraint);

    if ($value === NULL || $value === '') {
      return;
    }

    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }

    if (!$this->isValid($value)) {
      $this->context->addViolation($constraint->message);
    }
  }

  /**
   * Return TRUE if value is a valid hostname or IP address literal.
   */
  protected function isValid(string $value): bool {
    if (filter_var($value, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME) !== FALSE) {
      return TRUE;
    }

    if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
      $address = substr($value, 1, strlen($value) - 2);
      if (filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserNameConstraintValidator.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UserName constraint.
 */
class UserNameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items) || !$items->value) {
      $this->context->addViolation($constraint->emptyMessage);
      return;
    }
    $name = $items->first()->value;
    if (substr($name, 0, 1) == ' ') {
      $this->context->addViolation($constraint->spaceBeginMessage);
    }
    if (substr($name, -1) == ' ') {
      $this->context->addViolation($constraint->spaceEndMessage);
    }
    if (strpos($name, '  ') !== FALSE) {
      $this->context->addViolation($constraint->multipleSpacesMessage);
    }
    if (preg_match('/[^\x{80}-\x{F7} a-z0-9@_.\'-]/i', $name)
      || preg_match(
        '/[\x{80}-\x{A0}' .       // Non-printable ISO-8859-1 + NBSP
        '\x{AD}' .                // Soft-hyphen
        '\x{2000}-\x{200F}' .     // Various space characters
        '\x{2028}-\x{202F}' .     // Bidirectional text overrides
        '\x{205F}-\x{206F}' .     // Various text hinting characters
        '\x{FEFF}' .              // Byte order mark
        '\x{FF01}-\x{FF60}' .     // Full-width latin
        '\x{FFF9}-\x{FFFD}' .     // Replacement characters
        '\x{0}-\x{1F}]/u', // NULL byte and control characters
        $name)
    ) {
      $this->context->addViolation($constraint->illegalMessage);
    }
    if (Unicode::strlen($name) > USERNAME_MAX_LENGTH) {
      $this->context->addViolation($constraint->tooLongMessage, array('%name' => $name, '%max' => USERNAME_MAX_LENGTH));
    }
  }
}

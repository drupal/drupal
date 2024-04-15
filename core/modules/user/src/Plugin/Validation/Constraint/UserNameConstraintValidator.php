<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\user\UserInterface;
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
    if (empty($items) || ($items instanceof FieldItemListInterface && $items->isEmpty())) {
      $this->context->addViolation($constraint->emptyMessage);
      return;
    }
    $name = $items instanceof FieldItemListInterface ? $items->first()->value : $items;
    if (str_starts_with($name, ' ')) {
      $this->context->addViolation($constraint->spaceBeginMessage);
    }
    if (str_ends_with($name, ' ')) {
      $this->context->addViolation($constraint->spaceEndMessage);
    }
    if (str_contains($name, '  ')) {
      $this->context->addViolation($constraint->multipleSpacesMessage);
    }
    if (preg_match('/[^\x{80}-\x{F7} a-z0-9@+_.\'-]/i', $name)
      || preg_match(
        // Non-printable ISO-8859-1 + NBSP
        '/[\x{80}-\x{A0}' .
        // Soft-hyphen
        '\x{AD}' .
        // Various space characters
        '\x{2000}-\x{200F}' .
        // Bidirectional text overrides
        '\x{2028}-\x{202F}' .
        // Various text hinting characters
        '\x{205F}-\x{206F}' .
        // Byte order mark
        '\x{FEFF}' .
        // Full-width latin
        '\x{FF01}-\x{FF60}' .
        // Replacement characters
        '\x{FFF9}-\x{FFFD}' .
        // NULL byte and control characters
        '\x{0}-\x{1F}]/u',
        $name)
    ) {
      $this->context->addViolation($constraint->illegalMessage);
    }
    if (mb_strlen($name) > UserInterface::USERNAME_MAX_LENGTH) {
      $this->context->addViolation($constraint->tooLongMessage, ['%name' => $name, '%max' => UserInterface::USERNAME_MAX_LENGTH]);
    }
  }

}

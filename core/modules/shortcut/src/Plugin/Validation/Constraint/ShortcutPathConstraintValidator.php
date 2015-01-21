<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Validation\Constraint\ShortcutPathConstraintValidator.
 */

namespace Drupal\shortcut\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the shortcut path.
 */
class ShortcutPathConstraintValidator extends ConstraintValidator {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // We cannot use $items here because this is a computed field, we need to
    // fetch the value direct from TypedData.
    $list = $this->context->getMetadata()->getTypedData();
    if (empty($list)) {
      return;
    }
    $value = $list->value;

    if (!$this->pathValidator()->isValid($value)) {
      $this->context->addViolation($constraint->message);
    }
  }

  /**
   * Gets the path validator.
   *
   * @return \Drupal\Core\Path\PathValidatorInterface
   */
  protected function pathValidator() {
    if (!isset($this->pathValidator)) {
      $this->pathValidator = \Drupal::pathValidator();
    }
    return $this->pathValidator;
  }

}

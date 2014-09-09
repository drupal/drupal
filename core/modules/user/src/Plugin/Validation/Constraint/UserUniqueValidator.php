<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserUniqueValidator.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the unique user property constraint, such as name and email.
 */
class UserUniqueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items)) {
      return;
    }
    $field_name = $items->getFieldDefinition()->getName();

    $value_taken = (bool) \Drupal::entityQuery('user')
      // The UID could be NULL, so we cast it to 0 in that case.
      ->condition('uid', (int) $items->getEntity()->id(), '<>')
      ->condition($field_name, db_like($items->first()->value), 'LIKE')
      ->range(0, 1)
      ->count()
      ->execute();

    if ($value_taken) {
      $this->context->addViolation($constraint->message, array("%value" => $items->value));
    }
  }
}

<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Checks if the user's email address is provided if required.
 *
 * The user mail field is NOT required if account originally had no mail set
 * and the user performing the edit has 'administer users' permission.
 * This allows users without email address to be edited and deleted.
 */
class UserMailRequiredValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    /* @var \Drupal\user\UserInterface $account */
    $account = $items->getEntity();
    if (!isset($account)) {
      return;
    }

    $existing_value = NULL;

    // Only validate for existing user.
    if (!$account->isNew()) {
      $account_unchanged = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadUnchanged($account->id());
      $existing_value = $account_unchanged->getEmail();
    }

    $required = !(!$existing_value && \Drupal::currentUser()->hasPermission('administer users'));

    if ($required && (!isset($items) || $items->isEmpty())) {
      $this->context->addViolation($constraint->message, ['@name' => $account->getFieldDefinition('mail')->getLabel()]);
    }
  }

}

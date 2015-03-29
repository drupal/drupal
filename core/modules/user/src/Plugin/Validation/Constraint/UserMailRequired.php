<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UserMailRequired.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Checks if the user's email address is provided if required.
 *
 * The user mail field is NOT required if account originally had no mail set
 * and the user performing the edit has 'administer users' permission.
 * This allows users without email address to be edited and deleted.
 *
 * @Plugin(
 *   id = "UserMailRequired",
 *   label = @Translation("User email required", context = "Validation")
 * )
 */
class UserMailRequired extends Constraint implements ConstraintValidatorInterface {

  /**
   * Violation message. Use the same message as FormValidator.
   *
   * @var string
   */
  public $message = '!name field is required.';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->context->getMetadata()->getTypedData()->getEntity();
    $existing_value = NULL;
    if ($account->id()) {
      $account_unchanged = \Drupal::entityManager()
        ->getStorage('user')
        ->loadUnchanged($account->id());
      $existing_value = $account_unchanged->getEmail();
    }

    $required = !(!$existing_value && \Drupal::currentUser()->hasPermission('administer users'));

    if ($required && (!isset($items) || $items->isEmpty())) {
      $this->context->addViolation($this->message, array('!name' => SafeMarkup::placeholder($account->getFieldDefinition('mail')->getLabel())));
    }
  }

}

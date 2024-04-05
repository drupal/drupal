<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Core\Validation\BasicRecursiveValidatorFactory;
use Drupal\Core\Validation\ConstraintManager;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Provides a username validator.
 *
 * This validator re-uses the UserName constraint plugin but does not require a
 * User entity.
 */
class UserNameValidator {

  public function __construct(
    protected readonly BasicRecursiveValidatorFactory $validatorFactory,
    protected readonly ConstraintManager $constraintManager,
  ) {}

  /**
   * Validates a user name.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The list of constraint violations.
   */
  public function validateName(string $name): ConstraintViolationListInterface {
    $validator = $this->validatorFactory->createValidator();
    $constraint = $this->constraintManager->create('UserName', []);
    return $validator->validate($name, $constraint);
  }

}

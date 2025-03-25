<?php

declare(strict_types=1);

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a role exists.
 */
#[Constraint(
  id: 'RoleExists',
  label: new TranslatableMarkup('Role exists', [], ['context' => 'Validation'])
)]
class RoleExistsConstraint extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public $message = "The role with id '@rid' does not exist.";

}

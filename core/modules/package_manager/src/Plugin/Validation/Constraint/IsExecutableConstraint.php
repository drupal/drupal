<?php

declare(strict_types=1);

namespace Drupal\package_manager\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates that a value is the path of an executable file.
 */
#[Constraint(
  id: 'IsExecutable',
  label: new TranslatableMarkup('Is executable', [], ['context' => 'Validation'])
)]
final class IsExecutableConstraint extends SymfonyConstraint {

  /**
   * The error message shown when the path is not executable.
   *
   * @var string
   */
  public string $message = '"@path" is not an executable file.';

}

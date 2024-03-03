<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation File constraint.
 */
#[Constraint(
  id: 'FileValidation',
  label: new TranslatableMarkup('File Validation', [], ['context' => 'Validation'])
)]
class FileValidationConstraint extends SymfonyConstraint {

}

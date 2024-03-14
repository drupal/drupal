<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Supports validating file URIs.
 */
#[Constraint(
  id: 'FileUriUnique',
  label: new TranslatableMarkup('File URI', [], ['context' => 'Validation'])
)]
class FileUriUnique extends SymfonyConstraint {

  public $message = 'The file %value already exists. Enter a unique file URI.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}

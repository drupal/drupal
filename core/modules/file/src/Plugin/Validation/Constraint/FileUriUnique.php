<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator;

/**
 * Supports validating file URIs.
 */
#[Constraint(
  id: 'FileUriUnique',
  label: new TranslatableMarkup('File URI', [], ['context' => 'Validation'])
)]
class FileUriUnique extends UniqueFieldConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public $message = 'The file %value already exists. Enter a unique file URI.';

  /**
   * This constraint is case-sensitive.
   *
   * For example "public://foo.txt" and "public://FOO.txt" are treated as
   * different values, and can co-exist.
   *
   * @var bool
   */
  public $caseSensitive = TRUE;

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return UniqueFieldValueValidator::class;
  }

}

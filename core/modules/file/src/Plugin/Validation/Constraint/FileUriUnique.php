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

  public function __construct(
    mixed $options = NULL,
    ?bool $caseSensitive = NULL,
    $message = 'The file %value already exists. Enter a unique file URI.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    $this->caseSensitive = $caseSensitive ?? TRUE;
    parent::__construct($options, $caseSensitive, $message, $groups, $payload);

  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return UniqueFieldValueValidator::class;
  }

}

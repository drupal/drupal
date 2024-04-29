<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;

/**
 * Count constraint.
 *
 * Overrides the symfony constraint to use the strict setting.
 */
#[Constraint(
  id: 'Email',
  label: new TranslatableMarkup('Email', [], ['context' => 'Validation'])
)]
class EmailConstraint extends Email {

  /**
   * {@inheritdoc}
   */
  public function __construct(...$args) {
    $this->mode = static::VALIDATION_MODE_STRICT;
    parent::__construct(...$args);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return EmailValidator::class;
  }

}

<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Email;

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
  public function __construct($options = []) {
    $options += ['mode' => 'strict'];
    parent::__construct($options);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return EmailValidator::class;
  }

}

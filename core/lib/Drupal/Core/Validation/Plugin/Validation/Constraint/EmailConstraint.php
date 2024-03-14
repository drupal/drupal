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
  public function __construct(...$args) {
    $this->mode = static::VALIDATION_MODE_STRICT;
    parent::__construct(...$args);
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The name of the class that validates this constraint.
   *
   * @todo Add method return type declaration.
   * @see https://www.drupal.org/project/drupal/issues/3425150
   */
  public function validatedBy() {
    return EmailValidator::class;
  }

}

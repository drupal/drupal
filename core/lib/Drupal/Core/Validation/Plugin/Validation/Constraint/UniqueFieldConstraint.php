<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if an entity field has a unique value.
 */
#[Constraint(
  id: 'UniqueField',
  label: new TranslatableMarkup('Unique field constraint', [], ['context' => 'Validation'])
)]
class UniqueFieldConstraint extends SymfonyConstraint {

  public $message = 'A @entity_type with @field_name %value already exists.';

  /**
   * Returns the name of the class that validates this constraint.
   *
   * @return string
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}

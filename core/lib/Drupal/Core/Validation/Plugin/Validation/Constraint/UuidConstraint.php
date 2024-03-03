<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Constraints\UuidValidator;

/**
 * Validates a UUID.
 */
#[Constraint(
  id: 'Uuid',
  label: new TranslatableMarkup('Universally Unique Identifier', [], ['context' => 'Validation'])
)]
class UuidConstraint extends Uuid {

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
    return UuidValidator::class;
  }

}

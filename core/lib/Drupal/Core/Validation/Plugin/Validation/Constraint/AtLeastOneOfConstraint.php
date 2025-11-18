<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\CompositeConstraintInterface;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\AtLeastOneOfValidator;

/**
 * Checks that at least one of the given constraint is satisfied.
 *
 * Overrides the symfony constraint to convert the array of constraints to array
 * of constraint objects and use them.
 */
#[Constraint(
  id: 'AtLeastOneOf',
  label: new TranslatableMarkup('At least one of', [], ['context' => 'Validation'])
)]
class AtLeastOneOfConstraint extends AtLeastOneOf implements CompositeConstraintInterface {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeOptionStatic(): string {
    return 'constraints';
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return AtLeastOneOfValidator::class;
  }

}

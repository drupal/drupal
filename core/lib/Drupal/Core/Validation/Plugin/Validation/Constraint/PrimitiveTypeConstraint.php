<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating all primitive types.
 *
 * @Constraint(
 *   id = "PrimitiveType",
 *   label = @Translation("Primitive type", context = "Validation")
 * )
 */
class PrimitiveTypeConstraint extends Constraint {

  public $message = 'This value should be of the correct primitive type.';

}

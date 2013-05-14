<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraints\Type as SymfonyConstraint;

/**
 * Supports validating all primitive types.
 *
 * @todo: Move this below the TypedData core component.
 *
 * @Plugin(
 *   id = "PrimitiveType",
 *   label = @Translation("Primitive type", context = "Validation")
 * )
 */
class PrimitiveTypeConstraint extends SymfonyConstraint {

  public $message = 'This value should be of type %type.';
}

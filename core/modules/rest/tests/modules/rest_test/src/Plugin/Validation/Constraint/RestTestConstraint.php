<?php

namespace Drupal\rest_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Adds some validations for a REST test field.
 *
 * @Constraint(
 *   id = "rest_test_validation",
 *   label = @Translation("REST test validation", context = "Validation")
 * )
 *
 * @see \Drupal\Core\TypedData\OptionsProviderInterface
 */
class RestTestConstraint extends Constraint {

  public $message = 'REST test validation failed';

}

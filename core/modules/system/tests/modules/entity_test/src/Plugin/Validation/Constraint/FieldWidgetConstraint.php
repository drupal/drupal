<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating widget constraints.
 *
 * @Constraint(
 *   id = "FieldWidgetConstraint",
 *   label = @Translation("Field widget constraint.")
 * )
 */
class FieldWidgetConstraint extends Constraint {

  public $message = 'Widget constraint has failed.';

}

<?php

namespace Drupal\field_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Checks if a value is not equal.
 *
 * @Constraint(
 *   id = "TestField",
 *   label = @Translation("Test Field", context = "Validation"),
 *   type = { "integer" }
 * )
 */
class TestFieldConstraint extends NotEqualTo {

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\NotEqualToValidator';
  }

}

<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Validation\Constraint\TestFieldConstraint.
 */

namespace Drupal\field_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Checks if a value is not equal.
 *
 * @Plugin(
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
    return array('value');
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\NotEqualToValidator';
  }

}

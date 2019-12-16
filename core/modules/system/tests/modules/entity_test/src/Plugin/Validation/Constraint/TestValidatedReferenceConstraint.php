<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates referenced entities.
 *
 * @Constraint(
 *   id = "TestValidatedReferenceConstraint",
 *   label = @Translation("Test validated reference constraint.")
 * )
 */
class TestValidatedReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Invalid referenced entity.';

}

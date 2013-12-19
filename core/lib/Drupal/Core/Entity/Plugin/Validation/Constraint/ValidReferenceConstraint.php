<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Plugin(
 *   id = "ValidReference",
 *   label = @Translation("Entity Reference valid reference", context = "Validation")
 * )
 */
class ValidReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The referenced entity (%type: %id) does not exist.';

}

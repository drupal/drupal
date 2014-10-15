<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\ReferenceAccessConstraint.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Plugin(
 *   id = "ReferenceAccess",
 *   label = @Translation("Entity Reference reference access", context = "Validation")
 * )
 */
class ReferenceAccessConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'You do not have access to the referenced entity (%type: %id).';

}

<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a base class for constraints validating multiple fields.
 *
 * The constraint must be defined on entity-level; i.e., added to the entity
 * type.
 *
 * @see \Drupal\Core\Entity\EntityType::addConstraint
 */
abstract class CompositeConstraintBase extends Constraint {

  /**
   * An array of entity fields which should be passed to the validator.
   *
   * @return string[]
   *   An array of field names.
   */
  abstract public function coversFields();

}

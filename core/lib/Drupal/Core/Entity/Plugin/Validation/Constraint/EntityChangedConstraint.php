<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @Constraint(
 *   id = "EntityChanged",
 *   label = @Translation("Entity changed", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class EntityChangedConstraint extends Constraint {

  public $message = 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.';

}

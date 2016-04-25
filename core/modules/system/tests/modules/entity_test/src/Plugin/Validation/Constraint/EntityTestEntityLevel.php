<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint on entity entity level.
 *
 * @Constraint(
 *   id = "EntityTestEntityLevel",
 *   label = @Translation("Constraint on the entity level."),
 *   type = "entity"
 * )
 */
class EntityTestEntityLevel extends Constraint {

  public $message = 'Entity level validation';

}

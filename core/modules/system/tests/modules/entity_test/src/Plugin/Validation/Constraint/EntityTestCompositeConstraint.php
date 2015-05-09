<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Validation\Constraint\EntityTestCompositeConstraint.
 */

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Constraint with multiple fields.
 *
 * @Plugin(
 *   id = "EntityTestComposite",
 *   label = @Translation("Constraint with multiple fields."),
 *   type = "entity"
 * )
 */
class EntityTestCompositeConstraint extends CompositeConstraintBase {

  public $message = 'Multiple fields are validated';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['name', 'type'];
  }

}

<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * The entity reference supported new entities constraint.
 *
 * @Constraint(
 *   id = "EntityReferenceSupportedNewEntities",
 *   label = @Translation("Entity Reference Supported New Entities", context = "Validation"),
 * )
 */
class EntityReferenceSupportedNewEntitiesConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = '%collection_label can only be created in the default workspace.';

}

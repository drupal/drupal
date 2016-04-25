<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid entity type.
 *
 * @Constraint(
 *   id = "EntityType",
 *   label = @Translation("Entity type", context = "Validation"),
 *   type = { "entity", "entity_reference" }
 * )
 */
class EntityTypeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The entity must be of type %type.';

  /**
   * The entity type option.
   *
   * @var string
   */
  public $type;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'type';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('type');
  }
}

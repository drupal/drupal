<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is an entity that has a specific field.
 *
 * @Constraint(
 *   id = "EntityHasField",
 *   label = @Translation("Entity has field", context = "Validation"),
 *   type = { "entity" },
 * )
 */
class EntityHasFieldConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The entity must have the %field_name field.';

  /**
   * The violation message for non-fieldable entities.
   *
   * @var string
   */
  public $notFieldableMessage = 'The entity does not support fields.';

  /**
   * The field name option.
   *
   * @var string
   */
  public $field_name;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'field_name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return (array) $this->getDefaultOption();
  }

}

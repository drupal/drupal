<?php

declare(strict_types = 1);

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if config entity properties have been changed.
 *
 * @Constraint(
 *   id = "ImmutableProperties",
 *   label = @Translation("Properties are unchanged", context = "Validation"),
 *   type = { "entity" }
 * )
 */
class ImmutablePropertiesConstraint extends Constraint {

  /**
   * The error message if an immutable property has been changed.
   *
   * @var string
   */
  public string $message = "The '@name' property cannot be changed.";

  /**
   * The names of the immutable properties.
   *
   * @var string[]
   */
  public array $properties = [];

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'properties';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['properties'];
  }

}

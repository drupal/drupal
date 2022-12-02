<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that config dependencies contain specific types of entities.
 *
 * @Constraint(
 *   id = "RequiredConfigDependencies",
 *   label = @Translation("Required config dependency types", context = "Validation")
 * )
 */
class RequiredConfigDependenciesConstraint extends Constraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'This @entity_type requires a @dependency_type.';

  /**
   * The IDs of entity types that need to exist in config dependencies.
   *
   * For example, if an entity requires a filter format in its config
   * dependencies, this should contain `filter_format`.
   *
   * @var string[]
   */
  public array $entityTypes = [];

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['entityTypes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'entityTypes';
  }

}

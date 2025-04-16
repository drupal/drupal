<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;

/**
 * Checks that at least one of the given constraint is satisfied.
 *
 * Overrides the symfony constraint to convert the array of constraints to array
 * of constraint objects and use them.
 */
#[Constraint(
  id: 'AtLeastOneOf',
  label: new TranslatableMarkup('At least one of', [], ['context' => 'Validation'])
)]
class AtLeastOneOfConstraint extends AtLeastOneOf implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $constraint_manager = $container->get('validation.constraint');
    $constraints = $configuration['constraints'];
    $constraint_instances = [];
    foreach ($constraints as $constraint_id => $constraint) {
      foreach ($constraint as $constraint_name => $constraint_options) {
        $constraint_instances[$constraint_id] = $constraint_manager->create($constraint_name, $constraint_options);
      }
    }

    return new static($constraint_instances, [SymfonyConstraint::DEFAULT_GROUP]);
  }

}

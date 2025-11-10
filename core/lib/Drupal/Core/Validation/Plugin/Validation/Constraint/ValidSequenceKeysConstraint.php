<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Constraints\Existence;

/**
 * Checks that all the keys of a sequence match the specified constraints.
 */
#[Constraint(
  id: 'ValidSequenceKeys',
  label: new TranslatableMarkup('Valid sequence keys', [], ['context' => 'Validation']),
  type: ['sequence']
)]
class ValidSequenceKeysConstraint extends Existence implements ContainerFactoryPluginInterface {

  /**
   * The error message if a sequence key is invalid.
   *
   * @var string
   */
  public string $message = 'The keys of the sequence do not match the given constraints.';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $constraint_manager = $container->get('validation.constraint');
    $constraints = $configuration['constraints'];
    $constraint_instances = [];
    foreach ($constraints as $constraint_name => $constraint_options) {
      $constraint_instances[] = $constraint_manager->create($constraint_name, $constraint_options);
    }

    return new static($constraint_instances, [SymfonyConstraint::DEFAULT_GROUP]);
  }

}

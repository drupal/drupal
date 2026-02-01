<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Complex data constraint.
 *
 * Validates properties of complex data structures.
 */
#[Constraint(
  id: 'ComplexData',
  label: new TranslatableMarkup('Complex data', [], ['context' => 'Validation'])
)]
class ComplexDataConstraint extends SymfonyConstraint {

  /**
   * An array of constraints for contained properties, keyed by property name.
   *
   * @var array
   */
  public $properties;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?array $properties = NULL,
    ?array $groups = NULL,
    mixed $payload = NULL,
    ...$otherProperties,
  ) {
    // Allow skipping the 'properties' key in the options.
    if (is_array($options)) {
      if (!array_key_exists('properties', $options)) {
        $options = ['properties' => $options];
      }
    }
    elseif ($properties === NULL && !empty($otherProperties)) {
      $properties = $otherProperties;
    }
    parent::__construct($options, $groups, $payload);
    $this->properties = $properties ?? $this->properties;
    $constraint_manager = \Drupal::service('validation.constraint');

    // Instantiate constraint objects for array definitions.
    foreach ($this->properties as &$constraints) {
      foreach ($constraints as $id => $options) {
        if (!is_object($options)) {
          $constraints[$id] = $constraint_manager->create($id, $options);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'properties';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['properties'];
  }

}
